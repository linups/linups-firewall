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

    // Banned IPs older than this are removed by `clear:old-banned-ip`.
    'ban_duration_days' => (int) env('ban_duration_days', 30),

    // Keyword synchronisation with a "main" project running this package.
    'sync_with_main_project' => env('sync_with_main_project'),
    'sync_project_endpoint' => env('sync_project_endpoint'),

    // Shared secret for /linups-firewall/v1/get-keyword-list. When set, the endpoint
    // requires it as a Bearer token and `sync:Keywords` sends it. Empty = public.
    'keyword_list_token' => env('keyword_list_token'),
];