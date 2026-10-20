<?php

namespace App\Console\Commands;

use App\Actions\Holds\ExpireHoldsAction;
use Illuminate\Console\Command;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'holds:release-expired';

    protected $description = 'Release seats whose hold has expired';

    public function handle(ExpireHoldsAction $expire): int
    {
        $count = $expire->execute();

        $this->info("Released {$count} expired hold(s).");

        return self::SUCCESS;
    }
}