<?php

namespace App\Http\Requests\Lead;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'max:100'],
            'phone' => ['nullable', 'max:50'],
            'email' => ['nullable', 'max:100', 'email'],
            'company' => ['nullable', 'max:100'],
            'industry' => ['nullable', 'max:100'],
            'source' => ['nullable', 'max:50'],
            'status' => ['required', 'max:50'],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('account_id', Auth::user()->account_id)
                        ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SALE]);
                }),
            ],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
        ];
    }
}
