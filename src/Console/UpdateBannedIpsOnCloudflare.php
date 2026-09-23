<?php

namespace Linups\LinupsFirewall\Console;

use Illuminate\Console\Command;
use Linups\LinupsFirewall\Models\BannedIp;
use Linups\LinupsFirewall\Services\LinupsFirewallService;

class UpdateBannedIpsOnCloudflare extends Command
{

    protected $signature = 'update:banned-ips-on-cloudflare';


    protected $description = 'Every day system will update banned ips on cloudflare. (after cron will clear old banned ips)';


    public function handle(LinupsFirewallService $LinupsFirewallService): void
    {
        $ipArray = BannedIp::query()->distinct()->limit(10000)->pluck('ip')->all();

        if (count($ipArray) > 0) {
            $response = $LinupsFirewallService->updateIpListOnCloudflare($ipArray);
            $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }
}