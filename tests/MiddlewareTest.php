<?php

namespace Linups\LinupsFirewall\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Linups\LinupsFirewall\Models\BannedIp;
use Linups\LinupsFirewall\Models\Keyword;

class MiddlewareTest extends TestCase
{
    public function test_request_without_keyword_passes(): void
    {
        Keyword::create(['keyword' => 'wp-login']);

        $this->get('/products/1')->assertOk()->assertSee('ok');

        $this->assertSame(0, BannedIp::count());
        Http::assertNothingSent();
    }

    public function test_request_with_keyword_is_banned_and_pushed_to_cloudflare(): void
    {
        Http::fake([self::LIST_ITEMS_URL => Http::response(['success' => true])]);
        Keyword::create(['keyword' => 'wp-login']);

        $this->get('/WP-LOGIN.php')->assertForbidden();

        $this->assertTrue(BannedIp::where('ip', '127.0.0.1')->exists());
        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && $r->url() === self::LIST_ITEMS_URL
            && $r->data() === [['ip' => '127.0.0.1']]
            && $r->hasHeader('X-Auth-Key', 'global-key'));
    }

    public function test_request_is_still_blocked_when_cloudflare_fails(): void
    {
        Http::fake([self::LIST_ITEMS_URL => Http::response(['success' => false], 400)]);
        Keyword::create(['keyword' => 'wp-login']);

        $this->get('/wp-login.php')->assertForbidden();

        $this->assertSame(1, BannedIp::count());
    }

    public function test_empty_keyword_does_not_ban_everyone(): void
    {
        Keyword::create(['keyword' => '']);

        $this->get('/')->assertOk();
    }

    public function test_ipv6_clients_are_not_banned(): void
    {
        Keyword::create(['keyword' => 'wp-login']);

        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8::1'])
            ->get('/wp-login.php')
            ->assertOk();

        $this->assertSame(0, BannedIp::count());
    }

    public function test_keyword_cache_is_refreshed_when_keywords_change(): void
    {
        Http::fake([self::LIST_ITEMS_URL => Http::response(['success' => true])]);

        $this->get('/xmlrpc.php')->assertOk();   // caches an empty keyword list
        Keyword::create(['keyword' => 'xmlrpc']);

        $this->get('/xmlrpc.php')->assertForbidden();
    }

    public function test_api_token_is_preferred_over_global_key(): void
    {
        config(['linups-config.cloudflare_api_token' => 'scoped-token']);
        Http::fake([self::LIST_ITEMS_URL => Http::response(['success' => true])]);
        Keyword::create(['keyword' => 'wp-login']);

        $this->get('/wp-login.php')->assertForbidden();

        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer scoped-token')
            && ! $r->hasHeader('X-Auth-Key'));
    }

    public function test_banned_crawler_sends_no_mail(): void
    {
        config([
            'linups-config.notification_email' => 'admin@example.test',
            'linups-config.not_found_notification' => true,
        ]);
        Http::fake([self::LIST_ITEMS_URL => Http::response(['success' => true])]);
        Keyword::create(['keyword' => 'wp-login']);

        $this->get('/wp-login.php')->assertForbidden();

        $this->assertCount(0, $this->app['mailer']->getSymfonyTransport()->messages());
    }
}