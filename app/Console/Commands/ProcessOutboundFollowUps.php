<?php

namespace App\Console\Commands;

use App\Services\OutboundSalesService;
use Illuminate\Console\Command;

class ProcessOutboundFollowUps extends Command
{
    protected $signature = 'outbound:process-followups';
    protected $description = 'Process outbound sales follow-up emails for campaigns that are due';

    public function handle(OutboundSalesService $service): int
    {
        $this->info('Processing outbound follow-ups...');

        $processed = $service->processFollowUps();

        $this->info("Processed {$processed} follow-up(s).");

        return self::SUCCESS;
    }
}
