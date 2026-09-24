<?php

namespace Linups\LinupsFirewall\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Linups\LinupsFirewall\Services\LinupsFirewallService;
use Linups\LinupsFirewall\Services\NotFoundNotifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LinupsFirewallMiddleware
{
    private const CHECKED = 'linups-firewall.checked';
    private const NOT_FOUND_REPORTED = 'linups-firewall.not-found-reported';

    public function __construct(
        private LinupsFirewallService $firewall,
        private NotFoundNotifier $notFoundNotifier,
    ) {
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

    /**
     * Runs after the response has been sent, so the 404 mail doesn't slow the visitor down.
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($response->getStatusCode() !== 404 || $request->attributes->get(self::NOT_FOUND_REPORTED)) {
            return;
        }
        $request->attributes->set(self::NOT_FOUND_REPORTED, true);

        try {
            $this->notFoundNotifier->notify($request);
        } catch (Throwable $ex) {
            Log::warning('linups-firewall: 404 notification failed: ' . $ex->getMessage());
        }
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