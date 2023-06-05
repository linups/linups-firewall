<?php

namespace Linups\LinupsFirewall\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncKeywords extends Command
{
    protected $signature = 'sync:Keywords';

    protected $description = 'Download keywords from main project';

    public function handle()
    {
        if(config('linups-config.sync_with_main_project') != 'enabled') return;

        $response = Http::get(config('linups-config.sync_project_endpoint').'/api/v1/get-keyword-list', [
            'page' => 1,
        ]);

        dd($response->getBody()->getContents());
    }
}
