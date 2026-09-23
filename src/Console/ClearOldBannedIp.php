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
        $days = (int) config('linups-config.ban_duration_days', 30);

        $deleted = BannedIp::where('created_at', '<', Carbon::now()->subDays($days))->delete();

        $this->line("Deleted {$deleted} banned ips older than {$days} days.");
    }
}