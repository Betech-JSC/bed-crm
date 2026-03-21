<?php

namespace App\Console\Commands;

use App\Services\AiTrendService;
use Illuminate\Console\Command;

class FetchAiTrends extends Command
{
    protected $signature = 'trends:fetch
        {--monitor= : Specific monitor ID to fetch}
        {--force : Force fetch regardless of schedule}';

    protected $description = 'Fetch AI trends from configured sources and send notifications';

    public function handle(AiTrendService $service): int
    {
        $monitorId = $this->option('monitor');
        $force = $this->option('force');

        if ($monitorId) {
            $monitor = \App\Models\AiTrendMonitor::find($monitorId);
            if (!$monitor) {
                $this->error("Monitor #{$monitorId} not found.");
                return Command::FAILURE;
            }

            $this->info("Fetching trends for monitor: {$monitor->name}...");
            $items = $service->fetchForMonitor($monitor);
            $this->info("Found " . count($items) . " new items.");
            return Command::SUCCESS;
        }

        $this->info('Fetching AI trends for all due monitors...');

        if ($force) {
            // Force all active monitors
            $monitors = \App\Models\AiTrendMonitor::active()->get();
            $results = ['processed' => 0, 'new_items' => 0, 'errors' => 0];

            foreach ($monitors as $monitor) {
                try {
                    $this->line("  → Processing: {$monitor->name} ({$monitor->source})");
                    $items = $service->fetchForMonitor($monitor);
                    $results['processed']++;
                    $results['new_items'] += count($items);
                    $this->line("    Found " . count($items) . " new items.");
                } catch (\Exception $e) {
                    $results['errors']++;
                    $this->error("    Error: {$e->getMessage()}");
                }
            }
        } else {
            $results = $service->fetchAllDue();
        }

        $this->newLine();
        $this->info("✅ Processed: {$results['processed']} monitors");
        $this->info("📦 New items: {$results['new_items']}");
        if ($results['errors'] > 0) {
            $this->warn("⚠️  Errors: {$results['errors']}");
        }

        return Command::SUCCESS;
    }
}
