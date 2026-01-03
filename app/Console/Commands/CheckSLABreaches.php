<?php

namespace App\Console\Commands;

use App\Services\SLATrackingService;
use Illuminate\Console\Command;

class CheckSLABreaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all pending SLAs and send breach notifications';

    /**
     * Execute the console command.
     */
    public function handle(SLATrackingService $slaService): int
    {
        $this->info('Checking SLA breaches...');

        $results = $slaService->checkAllPendingSLAs();

        $this->info("Checked: {$results['checked']} leads");
        $this->info("Warnings sent: {$results['warnings']}");
        $this->info("Breaches detected: {$results['breaches']}");

        return Command::SUCCESS;
    }
}
