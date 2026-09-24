<?php

return [
    // Middleware applied to the package's own routes (/linups-firewall/...).
    'middleware' => ['web'],

    // Cloudflare. Prefer a scoped API token (Account > Account Filter Lists > Edit);
    // the legacy email + Global API Key pair is used only when no token is set.
    'cloudflare_endpoint' => env('cloudflare_endpoint', 'https://api.cloudflare.com/client/v4/accounts'),
    'cloudflare_api_token' => env('cloudflare_api_token'),
    'cloudflare_auth_email' => env('cloudflare_auth_email'),
    'cloudflare_auth_key' => env('cloudflare_auth_key'),
    'cloudflare_list_id' => env('cloudflare_list_id'),
    'cloudflare_account_id' => env('cloudflare_account_id'),

    // Address that receives an e-mail for every banned crawler. Leave empty to disable.
    'notification_email' => env('notification_email'),

    // Also e-mail notification_email about every 404, with a signed "Add url to ban list" link.
    'not_found_notification' => (bool) env('not_found_notification', false),
    // The same URL is reported at most once per this many minutes (0 = every hit).
    'not_found_throttle_minutes' => (int) env('not_found_throttle_minutes', 60),
    // How long the "Add url to ban list" link stays valid.
    'ban_link_expires_days' => (int) env('ban_link_expires_days', 7),

    // Banned IPs older than this are removed by `clear:old-banned-ip`.
    'ban_duration_days' => (int) env('ban_duration_days', 30),

    // Keyword synchronisation with a "main" project running this package.
    'sync_with_main_project' => env('sync_with_main_project'),
    'sync_project_endpoint' => env('sync_project_endpoint'),

    // Shared secret for /linups-firewall/v1/get-keyword-list. When set, the endpoint
    // requires it as a Bearer token and `sync:Keywords` sends it. Empty = public.
    'keyword_list_token' => env('keyword_list_token'),
];