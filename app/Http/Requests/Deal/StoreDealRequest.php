<?php

namespace App\Http\Requests\Deal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_id' => [
                'nullable',
                Rule::exists('leads', 'id')->where(function ($query) {
                    $query->where('account_id', Auth::user()->account_id);
                }),
            ],
            'title' => ['required', 'max:200'],
            'stage' => ['required', 'max:50'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('account_id', Auth::user()->account_id)
                        ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE]);
                }),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
