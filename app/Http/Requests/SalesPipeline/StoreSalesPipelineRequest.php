<?php

namespace App\Http\Requests\SalesPipeline;

use App\Models\SalesPipeline;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalesPipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_id' => ['nullable', 'exists:sales_channels,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
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
            'audit_data.website' => ['nullable', 'array'],
            'audit_data.marketing' => ['nullable', 'array'],
            'audit_data.business' => ['nullable', 'array'],
        ];
    }
}
