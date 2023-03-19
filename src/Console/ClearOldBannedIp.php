<?php

namespace Linups\LinupsFirewall\Console;

use Illuminate\Console\Command;
use Linups\LinupsFirewall\Models\BannedIp;
use Carbon\Carbon;

class ClearOldBannedIp extends Command
{
    protected $signature = 'clear:old-banned-ip';

    protected $description = 'This command will be deleting old banned ips from database.';

    public function handle(): void
    {
        BannedIp::where('created_at', '<', Carbon::now()->subDays(30))->delete();
    }
}
