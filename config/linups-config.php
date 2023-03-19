<?php

return [
    'middleware' => ['web', 'linups-firewall'],
    'cloudflare_endpoint' => env('cloudflare_endpoint'),
    'cloudflare_auth_email' => env('cloudflare_auth_email'),
    'cloudflare_auth_key' => env('cloudflare_auth_key'),
    'cloudflare_list_id' => env('cloudflare_list_id'),
    'cloudflare_account_id' => env('cloudflare_account_id'),
    'notification_email' => env('notification_email'),
];