<?php

namespace App\Http\Requests\SalesPipeline;

use App\Models\SalesPipeline;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesPipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_id' => ['nullable', 'exists:leads,id'],
            'deal_id' => ['nullable', 'exists:deals,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'social_channel' => ['nullable', 'in:zalo,facebook,other'],
            'social_account' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'in:hot,warm,cold'],
            'notes' => ['nullable', 'string'],
            'audit_data' => ['nullable', 'array'],
            'proposal_summary' => ['nullable', 'string'],
            'discussion_notes' => ['nullable', 'string'],
            'quote_amount' => ['nullable', 'numeric', 'min:0'],
            'quote_valid_until' => ['nullable', 'date'],
            'quote_notes' => ['nullable', 'string'],
        ];
    }
}
