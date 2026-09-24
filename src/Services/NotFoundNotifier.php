<?php

namespace Linups\LinupsFirewall\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class NotFoundNotifier
{
    //--- Headers that may carry credentials never leave the server
    private const HIDDEN_HEADERS = [
        'cookie',
        'authorization',
        'proxy-authorization',
        'x-csrf-token',
        'x-xsrf-token',
        'php-auth-user',
        'php-auth-pw',
    ];

    /**
     * Mails the admin about a 404 with a signed link that opens the "ban this URL" form.
     */
    public function notify(Request $request): void
    {
        $email = config('linups-config.notification_email');
        if (! config('linups-config.not_found_notification') || blank($email)) {
            return;
        }

        //--- One mail per URL per window, so repeated hits don't flood the inbox
        $minutes = (int) config('linups-config.not_found_throttle_minutes', 60);
        if ($minutes > 0 && ! Cache::add('linups-firewall.not-found.' . sha1($request->fullUrl()), true, $minutes * 60)) {
            return;
        }

        //--- Only path + query are signed: behind a proxy (Cloudflare) the app may build the link
        //--- as https but see the incoming request as http, which would break an absolute signature
        $banUrl = url(URL::temporarySignedRoute(
            'linups-firewall.ban-url',
            now()->addDays((int) config('linups-config.ban_link_expires_days', 7)),
            ['url' => $request->getRequestUri()],
            false
        ));

        Mail::send('linups-firewall::emails.not-found', [
            'url' => $request->fullUrl(),
            'time' => now(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'referer' => $request->headers->get('referer'),
            'headers' => $this->headers($request),
            'banUrl' => $banUrl,
        ], function ($message) use ($email, $request) {
            $message->to($email)->subject('Page not found: ' . Str::limit($request->getRequestUri(), 80));
        });
    }

    private function headers(Request $request): array
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            if (! in_array(strtolower($name), self::HIDDEN_HEADERS, true)) {
                $headers[$name] = implode(', ', $values);
            }
        }

        return $headers;
    }
}