<?php

namespace Linups\LinupsFirewall\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Linups\LinupsFirewall\Models\BannedIp;
use Linups\LinupsFirewall\Models\Keyword;
use RuntimeException;
use Throwable;

class LinupsFirewallService
{
    public const KEYWORDS_CACHE_KEY = 'linups-firewall.keywords';

    private ?string $endpoint;
    private ?string $apiToken;
    private ?string $authEmail;
    private ?string $authKey;
    private ?string $listID;
    private ?string $accountID;

    public function __construct()
    {
        $this->endpoint = rtrim((string) config('linups-config.cloudflare_endpoint'), '/');
        $this->apiToken = config('linups-config.cloudflare_api_token');
        $this->authKey = config('linups-config.cloudflare_auth_key');
        $this->authEmail = config('linups-config.cloudflare_auth_email');
        $this->listID = config('linups-config.cloudflare_list_id');
        $this->accountID = config('linups-config.cloudflare_account_id');
    }

    public function getListsFromCloudflare()
    {
        return $this->cloudflare()->get($this->endpoint . '/' . $this->accountID . '/rules/lists')->body();
    }

    public function BanIpOnCloudflare(string $ip)
    {
        // Called during a web request, so keep the timeout short.
        $response = $this->cloudflare(5)->post($this->listItemsUrl(), [
            ['ip' => $this->toCloudflareIp($ip)],
        ]);

        if ($response->json('success') === true) {
            return $response->object();
        }

        throw new RuntimeException('Invalid response. Debug:' . $response->body());
    }

    /**
     * Returns true when the request URL contains a blocked keyword. The IP is then
     * stored locally and pushed to Cloudflare; a Cloudflare failure is logged but
     * does not un-ban the request.
     */
    public function checkIfRequestMadeByWebSpider(?Request $request = null): bool
    {
        $request ??= request();
        $ip = $request->ip();

        //--- Only IPv4 addresses are banned per request
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $keyword = $this->matchKeyword($request->fullUrl());
        if ($keyword === null) {
            return false;
        }

        BannedIp::firstOrCreate(['ip' => $ip]);

        Log::info('linups-firewall: banning crawler', [
            'ip' => $ip,
            'keyword' => $keyword,
            'uri' => $request->getRequestUri(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $this->BanIpOnCloudflare($ip);
        } catch (Throwable $ex) {
            Log::warning('linups-firewall: Cloudflare ban failed: ' . $ex->getMessage());
        }

        return true;
    }

    public function updateIpListOnCloudflare(array $ipList)
    {
        $ips = array_unique(array_map(fn ($ip) => $this->toCloudflareIp((string) $ip), $ipList));
        $items = array_values(array_map(fn ($ip) => ['ip' => $ip], $ips));

        $response = $this->cloudflare(60)->put($this->listItemsUrl(), $items);

        if ($response->json('success') !== true) {
            throw new RuntimeException('List not updated..' . $response->body());
        }

        return $response->object();
    }

    private function matchKeyword(string $url): ?string
    {
        $keywords = Cache::remember(self::KEYWORDS_CACHE_KEY, 60 * 60, function () {
            return Keyword::query()->pluck('keyword')->all();
        });

        foreach ($keywords as $keyword) {
            // An empty keyword would match every URL and ban every visitor.
            if ($keyword !== '' && $keyword !== null && stripos($url, $keyword) !== false) {
                return $keyword;
            }
        }

        return null;
    }

    /**
     * Cloudflare lists do not accept single IPv6 addresses, so they are widened to their /64.
     */
    private function toCloudflareIp(string $ip): string
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip;
        }

        $prefix = substr(inet_pton($ip), 0, 8) . str_repeat("\0", 8);

        return inet_ntop($prefix) . '/64';
    }

    private function listItemsUrl(): string
    {
        return $this->endpoint . '/' . $this->accountID . '/rules/lists/' . $this->listID . '/items';
    }

    private function cloudflare(int $timeout = 30): PendingRequest
    {
        $request = Http::acceptJson()->asJson()->timeout($timeout);

        if (filled($this->apiToken)) {
            return $request->withToken($this->apiToken);
        }

        return $request->withHeaders([
            'X-Auth-Email' => $this->authEmail,
            'X-Auth-Key' => $this->authKey,
        ]);
    }
}