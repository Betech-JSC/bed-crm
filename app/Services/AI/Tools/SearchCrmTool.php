<?php

namespace App\Services\AI\Tools;

use App\Contracts\AiToolInterface;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class SearchCrmTool implements AiToolInterface
{
    public function name(): string { return 'search_crm'; }

    public function description(): string
    {
        return 'Tìm kiếm leads, deals, contacts trong CRM. Sử dụng khi user hỏi về thông tin khách hàng hoặc deal cụ thể.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entity' => ['type' => 'string', 'enum' => ['lead', 'deal', 'contact'], 'description' => 'Loại entity cần tìm'],
                'query' => ['type' => 'string', 'description' => 'Từ khóa tìm kiếm (tên, email, công ty...)'],
                'status' => ['type' => 'string', 'description' => 'Filter theo status/stage'],
                'limit' => ['type' => 'integer', 'description' => 'Số kết quả tối đa', 'default' => 10],
            ],
            'required' => ['entity', 'query'],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $entity = $params['entity'];
        $query = $params['query'];
        $limit = min($params['limit'] ?? 10, 20);
        $accountId = $context['account_id'];

        $results = match ($entity) {
            'lead' => $this->searchLeads($query, $accountId, $params['status'] ?? null, $limit),
            'deal' => $this->searchDeals($query, $accountId, $params['status'] ?? null, $limit),
            'contact' => $this->searchContacts($query, $limit),
            default => [],
        };

        $count = count($results);
        return [
            'success' => true,
            'message' => "Tìm thấy {$count} {$entity}(s) cho \"{$query}\"",
            'data' => $results,
        ];
    }

    private function searchLeads(string $q, int $accountId, ?string $status, int $limit): array
    {
        return Lead::where('account_id', $accountId)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%");
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->limit($limit)
            ->get(['id', 'name', 'email', 'phone', 'company', 'status', 'score', 'source'])
            ->toArray();
    }

    private function searchDeals(string $q, int $accountId, ?string $status, int $limit): array
    {
        return Deal::where('account_id', $accountId)
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%");
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->limit($limit)
            ->get(['id', 'title', 'stage', 'status', 'value', 'expected_close_date', 'win_probability'])
            ->toArray();
    }

    private function searchContacts(string $q, int $limit): array
    {
        return Contact::where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone'])
            ->toArray();
    }

    public function requiresConfirmation(): bool { return false; }
}
