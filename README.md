# linups-firewall

Laravel package that blocks web crawlers. Every request URL is matched against the
`keywords` table; a match returns `403`, stores the client IP in `banned_ips` and adds it
to a Cloudflare IP list (which you reference from a Cloudflare WAF rule).

## Installation

```sh
composer require linups/linups-firewall
php artisan migrate
php artisan vendor:publish --tag=linups-config   # optional
```

The middleware is registered globally by the service provider. It is also available as the
`linups-firewall` route middleware alias.

## Configuration (`.env`)

| Key | Purpose |
| --- | --- |
| `cloudflare_api_token` | Scoped API token (*Account Filter Lists: Edit*). Recommended. |
| `cloudflare_auth_email`, `cloudflare_auth_key` | Legacy Global API Key auth, used only when no token is set. |
| `cloudflare_account_id`, `cloudflare_list_id` | Target IP list. |
| `cloudflare_endpoint` | Defaults to `https://api.cloudflare.com/client/v4/accounts`. |
| `notification_email` | Receives a mail (IP, URL, user agent) per banned crawler. Empty = off. |
| `not_found_notification` | `true` to also mail `notification_email` about every 404 (URL, time, request details) with an *Add url to ban list* link. Default off. |
| `not_found_throttle_minutes` | Report the same URL at most once per this many minutes, default `60`. `0` = every hit. |
| `ban_link_expires_days` | Validity of the signed *Add url to ban list* link, default `7`. |
| `ban_duration_days` | Local ban retention, default `30`. |
| `sync_with_main_project` | `enabled` to download keywords from another installation. |
| `sync_project_endpoint` | Base URL of that installation. |
| `keyword_list_token` | Shared secret protecting `/linups-firewall/v1/get-keyword-list`. Set the same value on both sides. |

## 404 notifications

With `not_found_notification=true`, each 404 sends a mail containing a link to
`/linups-firewall/ban-url`. The page needs no login: the link is a signed URL that expires
after `ban_link_expires_days`, so it cannot be forged or edited. The form is pre-filled with
the requested path, which you can shorten before saving it to the `keywords` table.
Signed links rely on `APP_KEY` and on the app seeing the correct scheme/host (configure
trusted proxies when running behind Cloudflare or a load balancer).

## Scheduled commands

| Command | Time | Action |
| --- | --- | --- |
| `clear:old-banned-ip` | 03:03 | Delete bans older than `ban_duration_days`. |
| `update:banned-ips-on-cloudflare` | 04:06 | Replace the Cloudflare list with the local bans (IPv6 as `/64`). |
| `sync:Keywords` | 05:09 | Import keywords from the main project. |

## Testing

```sh
composer install
composer test
```