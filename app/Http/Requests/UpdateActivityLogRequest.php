<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,done'],
            // A pending item must explain itself so the next person can pick it up.
            'remark' => ['nullable', 'string', 'max:1000', 'required_if:status,pending'],
        ];
    }

    public function messages(): array
    {
        return [
            'remark.required_if' => 'Add a remark explaining why this is still pending, so the next person knows where you stopped.',
        ];
    }
}
