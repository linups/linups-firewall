<?php

namespace Linups\LinupsFirewall;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use Linups\LinupsFirewall\Http\Middleware\LinupsFirewallMiddleware;
use Linups\LinupsFirewall\Console\ClearOldBannedIp;
use Linups\LinupsFirewall\Console\UpdateBannedIpsOnCloudflare;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Console\Scheduling\Schedule;


class LinupsFirewallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(Kernel $kernel): void
    {
        Route::prefix('linups-firewall')
            ->as('linups-firewall.')
            ->middleware(['web', 'linups-firewall'])
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });

/*        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('linups-firewall', LinupsFirewallMiddleware::class);
        $router->pushMiddlewareToGroup('web', LinupsFirewallMiddleware::class);*/
        $kernel->pushMiddleware(LinupsFirewallMiddleware::class);

        if ($this->app->runningInConsole()) {
    /*        $this->publishes([
                __DIR__.'/../resources/assets' => public_path('linups-firewall'),
            ], 'assets');*/
            //--- Loading migrations
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            $this->commands([
                ClearOldBannedIp::class,
                UpdateBannedIpsOnCloudflare::class,
            ]);
            //--- Cron
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('clear:old-banned-ip')->dailyAt('03:00');
                $schedule->command('update:banned-ips-on-cloudflare')->dailyAt('04:00');
            });
            // In addition to publishing assets, we also publish the config
            $this->publishes([
                __DIR__.'/../config/linups-config.php' => config_path('linups-config.php'),
            ], 'linups-config');
        }

    }
}
