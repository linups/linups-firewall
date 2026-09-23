<?php

namespace Linups\LinupsFirewall\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Linups\LinupsFirewall\Models\Keyword;

class KeywordListTest extends TestCase
{
    private const LIST_URL = '/linups-firewall/v1/get-keyword-list';

    public function test_list_is_returned_as_json(): void
    {
        Keyword::create(['keyword' => 'wp-login']);

        $this->get(self::LIST_URL)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson([['keyword' => 'wp-login']]);
    }

    public function test_list_requires_token_when_configured(): void
    {
        config(['linups-config.keyword_list_token' => 'secret']);

        $this->get(self::LIST_URL)->assertForbidden();
        $this->get(self::LIST_URL, ['Authorization' => 'Bearer wrong'])->assertForbidden();
        $this->get(self::LIST_URL, ['Authorization' => 'Bearer secret'])->assertOk();
    }

    public function test_sync_command_imports_keywords(): void
    {
        config([
            'linups-config.sync_with_main_project' => 'enabled',
            'linups-config.sync_project_endpoint' => 'https://main.test/',
            'linups-config.keyword_list_token' => 'secret',
        ]);
        Keyword::create(['keyword' => 'existing']);
        Http::fake(['main.test/*' => Http::response([
            ['keyword' => 'existing'], ['keyword' => 'xmlrpc'], ['keyword' => ''], 'garbage',
        ])]);

        $this->artisan('sync:Keywords')->assertSuccessful();

        $this->assertEqualsCanonicalizing(['existing', 'xmlrpc'], Keyword::pluck('keyword')->all());
        Http::assertSent(fn (Request $r) => $r->url() === 'https://main.test/linups-firewall/v1/get-keyword-list'
            && $r->hasHeader('Authorization', 'Bearer secret'));
    }

    public function test_sync_command_fails_cleanly_on_bad_response(): void
    {
        config([
            'linups-config.sync_with_main_project' => 'enabled',
            'linups-config.sync_project_endpoint' => 'https://main.test',
        ]);
        Http::fake(['main.test/*' => Http::response('Server Error', 500)]);

        $this->artisan('sync:Keywords')->assertFailed();
    }

    public function test_sync_command_does_nothing_when_disabled(): void
    {
        $this->artisan('sync:Keywords')->assertSuccessful();

        Http::assertNothingSent();
    }
}