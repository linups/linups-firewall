<?php

namespace Linups\LinupsFirewall\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Linups\LinupsFirewall\Services\LinupsFirewallService;

class LinupsFirewallMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $LinupsFirewallService = new LinupsFirewallService();

            if($LinupsFirewallService->checkIfRequestMadeByWebSpider()) {
                //--- For testing only
                mail(config('linups-config.notification_email'), 'Banned Crawler!', '<pre>'.print_r($_SERVER, true).'</pre>');
            } else {
                dd('not catched');
            }
        } catch (\Throwable $ex) {
            \Log::debug($ex->getMessage());
        } finally {
            abort(403, 'You\'re not allowed to crawl!');
        }

        return $next($request);
    }
}
