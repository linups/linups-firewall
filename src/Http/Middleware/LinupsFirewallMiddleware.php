<?php

namespace Linups\LinupsFirewall\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Linups\LinupsFirewall\Services\LinupsFirewallService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LinupsFirewallMiddleware
{
    private const CHECKED = 'linups-firewall.checked';

    public function __construct(private LinupsFirewallService $firewall)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        //--- The middleware is global and may also be attached to routes; check once.
        if ($request->attributes->get(self::CHECKED)) {
            return $next($request);
        }
        $request->attributes->set(self::CHECKED, true);

        try {
            if ($this->firewall->checkIfRequestMadeByWebSpider($request)) {
                $this->notify($request);

                return response('You\'re not allowed to crawl!', 403);
            }
        } catch (Throwable $ex) {
            Log::warning('linups-firewall: ' . $ex->getMessage());
        }

        return $next($request);
    }

    private function notify(Request $request): void
    {
        $email = config('linups-config.notification_email');
        if (blank($email)) {
            return;
        }

        try {
            Mail::raw(implode("\n", [
                'IP: ' . $request->ip(),
                'URL: ' . $request->fullUrl(),
                'User-Agent: ' . $request->userAgent(),
            ]), function ($message) use ($email) {
                $message->to($email)->subject('Banned Crawler!');
            });
        } catch (Throwable $ex) {
            Log::warning('linups-firewall: notification failed: ' . $ex->getMessage());
        }
    }
}