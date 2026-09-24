<?php

namespace Linups\LinupsFirewall;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Linups\LinupsFirewall\Console\ClearOldBannedIp;
use Linups\LinupsFirewall\Console\SyncKeywords;
use Linups\LinupsFirewall\Console\UpdateBannedIpsOnCloudflare;
use Linups\LinupsFirewall\Http\Middleware\LinupsFirewallMiddleware;

class LinupsFirewallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/linups-config.php', 'linups-config');
    }

    public function boot(Kernel $kernel, Router $router): void
    {
        $router->aliasMiddleware('linups-firewall', LinupsFirewallMiddleware::class);
        $kernel->pushMiddleware(LinupsFirewallMiddleware::class);

        Route::prefix('linups-firewall')
            ->middleware(config('linups-config.middleware', ['web']))
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'linups-firewall');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->commands([
                ClearOldBannedIp::class,
                UpdateBannedIpsOnCloudflare::class,
                SyncKeywords::class,
            ]);

            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('clear:old-banned-ip')->dailyAt('03:03');
                $schedule->command('update:banned-ips-on-cloudflare')->dailyAt('04:06');
                $schedule->command('sync:Keywords')->dailyAt('05:09');
            });

            $this->publishes([
                __DIR__ . '/../config/linups-config.php' => config_path('linups-config.php'),
            ], 'linups-config');
        }
    }
}