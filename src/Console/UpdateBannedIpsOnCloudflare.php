<?php

namespace Linups\LinupsFirewall\Console;

use Illuminate\Console\Command;
use Linups\LinupsFirewall\Models\BannedIp;
use Linups\LinupsFirewall\Services\LinupsFirewallService;

class UpdateBannedIpsOnCloudflare extends Command
{

    protected $signature = 'update:banned-ips-on-cloudflare';


    protected $description = 'Every day system will update banned ips on cloudflare. (after cron will clear old banned ips)';


    public function handle(): void
    {
        $LinupsFirewallService = new LinupsFirewallService();

        $bannedIpList = BannedIp::select('ip')->distinct()->limit(10000)->get();
        if($bannedIpList->isNotEmpty()) {
            $ipArray = [];
            foreach($bannedIpList as $ip) {
                $ipArray[] = $ip->ip;
            }
            $response = $LinupsFirewallService->updateIpListOnCloudflare($ipArray);
            $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    }
}
