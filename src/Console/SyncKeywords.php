<?php

namespace Linups\LinupsFirewall\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Linups\LinupsFirewall\Models\Keyword;

class SyncKeywords extends Command
{
    protected $signature = 'sync:Keywords';

    protected $description = 'Download keywords from main project';

    public function handle()
    {
        if(config('linups-config.sync_with_main_project') != 'enabled') return;

        $response = Http::get(config('linups-config.sync_project_endpoint').'/linups-firewall/v1/get-keyword-list', [
            'page' => 1,
        ]);

        $responseRaw = ($response->getBody()->getContents());
        $responseData = json_decode($responseRaw);
        if(count($responseData) > 0) {
            foreach($responseData as $keyword) {
                Keyword::updateOrCreate(['keyword' => $keyword->keyword]);
            }
        }
    }
}
