<?php

namespace Linups\LinupsFirewall\Tests;

use Illuminate\Support\Facades\Http;
use Linups\LinupsFirewall\Models\Keyword;

class LegacyPublishedConfigTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Configs published by older versions attach the alias to the package routes too.
        $app['config']->set('linups-config.middleware', ['web', 'linups-firewall']);
    }

    public function test_middleware_runs_once_on_package_routes(): void
    {
        Http::fake([self::LIST_ITEMS_URL => Http::response(['success' => true])]);
        Keyword::create(['keyword' => 'wp-login']);

        $this->get('/linups-firewall/v1/get-keyword-list?x=wp-login')->assertForbidden();

        Http::assertSentCount(1);
    }
}