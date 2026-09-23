<?php

namespace Linups\LinupsFirewall\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Linups\LinupsFirewall\Models\BannedIp;
use Linups\LinupsFirewall\Services\LinupsFirewallService;
use RuntimeException;

class CommandsTest extends TestCase
{
    public function test_update_replaces_cloudflare_list_and_widens_ipv6(): void
    {
        Http::fake([self::LIST_ITEMS_URL => Http::response(['success' => true])]);
        BannedIp::create(['ip' => '10.0.0.1']);
        BannedIp::create(['ip' => '2001:db8:1:2:3:4:5:6']);  // no "::" in it
        BannedIp::create(['ip' => '2001:db8:1:2::9']);       // same /64

        $this->artisan('update:banned-ips-on-cloudflare')->assertSuccessful();

        Http::assertSent(function (Request $r) {
            $ips = array_column($r->data(), 'ip');
            sort($ips);

            return $r->method() === 'PUT' && $ips === ['10.0.0.1', '2001:db8:1:2::/64'];
        });
    }

    public function test_update_does_nothing_without_banned_ips(): void
    {
        $this->artisan('update:banned-ips-on-cloudflare')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_service_throws_on_cloudflare_error(): void
    {
        Http::fake([self::LIST_ITEMS_URL => Http::response(['success' => false], 400)]);

        $this->expectException(RuntimeException::class);

        app(LinupsFirewallService::class)->updateIpListOnCloudflare(['10.0.0.1']);
    }

    public function test_clear_removes_only_expired_bans(): void
    {
        config(['linups-config.ban_duration_days' => 7]);
        BannedIp::create(['ip' => '10.0.0.1'])->forceFill(['created_at' => now()->subDays(8)])->save();
        BannedIp::create(['ip' => '10.0.0.2'])->forceFill(['created_at' => now()->subDays(6)])->save();

        $this->artisan('clear:old-banned-ip')->assertSuccessful();

        $this->assertSame(['10.0.0.2'], BannedIp::pluck('ip')->all());
    }

    public function test_config_resolves_without_publishing(): void
    {
        $this->assertSame(30, config('linups-config.ban_duration_days'));
        $this->assertSame(['web'], config('linups-config.middleware'));
    }
}