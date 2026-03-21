<?php

namespace App\Services\AI\Tools;

use App\Contracts\AiToolInterface;
use App\Models\Deal;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class GetReportTool implements AiToolInterface
{
    public function name(): string { return 'get_report'; }

    public function description(): string
    {
        return 'Lấy báo cáo kinh doanh theo thời gian: doanh thu, leads, deals, conversion rate. Sử dụng khi user hỏi về hiệu suất kinh doanh.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'period' => ['type' => 'string', 'enum' => ['today', 'this_week', 'this_month', 'this_quarter', 'this_year'], 'description' => 'Khoảng thời gian'],
                'metrics' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Metrics cần lấy: leads, deals, revenue, conversion'],
            ],
            'required' => ['period'],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $accountId = $context['account_id'];
        $period = $params['period'] ?? 'this_month';

        [$start, $end] = $this->periodRange($period);

        $report = [
            'period' => $period,
            'date_range' => ['from' => $start->format('Y-m-d'), 'to' => $end->format('Y-m-d')],
            'leads' => [
                'new' => Lead::where('account_id', $accountId)->whereBetween('created_at', [$start, $end])->count(),
                'by_status' => Lead::where('account_id', $accountId)
                    ->whereBetween('created_at', [$start, $end])
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'by_source' => Lead::where('account_id', $accountId)
                    ->whereBetween('created_at', [$start, $end])
                    ->select('source', DB::raw('count(*) as count'))
                    ->groupBy('source')
                    ->pluck('count', 'source')
                    ->toArray(),
            ],
            'deals' => [
                'new' => Deal::where('account_id', $accountId)->whereBetween('created_at', [$start, $end])->count(),
                'won' => Deal::where('account_id', $accountId)->where('status', 'won')->whereBetween('updated_at', [$start, $end])->count(),
                'lost' => Deal::where('account_id', $accountId)->where('status', 'lost')->whereBetween('updated_at', [$start, $end])->count(),
                'revenue_won' => (float) Deal::where('account_id', $accountId)->where('status', 'won')->whereBetween('updated_at', [$start, $end])->sum('value'),
                'pipeline_value' => (float) Deal::where('account_id', $accountId)->where('status', 'open')->sum('value'),
            ],
        ];

        // Calculate conversion rate
        $totalLeads = $report['leads']['new'] ?: 1;
        $wonDeals = $report['deals']['won'];
        $report['conversion_rate'] = round(($wonDeals / $totalLeads) * 100, 1);

        return [
            'success' => true,
            'message' => "Báo cáo {$period}: {$report['leads']['new']} leads mới, {$wonDeals} deals won, doanh thu " . number_format($report['deals']['revenue_won'], 0, ',', '.') . ' VND',
            'data' => $report,
        ];
    }

    private function periodRange(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    public function requiresConfirmation(): bool { return false; }
}
