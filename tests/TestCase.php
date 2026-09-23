<?php

namespace Linups\LinupsFirewall\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Linups\LinupsFirewall\LinupsFirewallServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected const LIST_ITEMS_URL = 'https://api.cloudflare.test/accounts/acc/rules/lists/list/items';

    protected function getPackageProviders($app): array
    {
        return [LinupsFirewallServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('linups-config.cloudflare_endpoint', 'https://api.cloudflare.test/accounts');
        $app['config']->set('linups-config.cloudflare_account_id', 'acc');
        $app['config']->set('linups-config.cloudflare_list_id', 'list');
        $app['config']->set('linups-config.cloudflare_auth_email', 'me@example.test');
        $app['config']->set('linups-config.cloudflare_auth_key', 'global-key');
    }

    protected function defineRoutes($router): void
    {
        Route::any('/{any?}', fn () => 'ok')->where('any', '.*');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }
}