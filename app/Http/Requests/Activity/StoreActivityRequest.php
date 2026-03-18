<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_type' => ['required', 'in:App\\Models\\Lead,App\\Models\\Deal,App\\Models\\Contact'],
            'subject_id' => ['required', 'integer'],
            'type' => ['required', 'in:call,email,meeting,note'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'date' => ['required', 'date'],
        ];
    }
}
