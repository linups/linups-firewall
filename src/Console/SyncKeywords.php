<?php

namespace Linups\LinupsFirewall\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Linups\LinupsFirewall\Models\Keyword;

class SyncKeywords extends Command
{
    protected $signature = 'sync:Keywords';

    protected $description = 'Download keywords from main project';

    public function handle(): int
    {
        if (config('linups-config.sync_with_main_project') != 'enabled') {
            return self::SUCCESS;
        }

        $endpoint = rtrim((string) config('linups-config.sync_project_endpoint'), '/');
        if ($endpoint === '') {
            $this->error('sync_project_endpoint is not configured.');

            return self::FAILURE;
        }

        $request = Http::acceptJson()->timeout(30);
        if (filled($token = config('linups-config.keyword_list_token'))) {
            $request = $request->withToken($token);
        }

        $response = $request->get($endpoint . '/linups-firewall/v1/get-keyword-list');
        $keywords = $response->json();

        if ($response->failed() || ! is_array($keywords)) {
            $this->error('Keyword download failed (HTTP ' . $response->status() . ').');

            return self::FAILURE;
        }

        $created = 0;
        foreach ($keywords as $item) {
            $keyword = is_array($item) ? (string) ($item['keyword'] ?? '') : '';
            if ($keyword === '') {
                continue;
            }
            if (Keyword::firstOrCreate(['keyword' => $keyword])->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->info("Received " . count($keywords) . " keywords, {$created} new.");

        return self::SUCCESS;
    }
}