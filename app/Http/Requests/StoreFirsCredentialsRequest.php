<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFirsCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only accountants / admins may configure FIRS credentials
        return auth()->check() && auth()->user()->isAccountant();
    }

    public function rules(): array
    {
        return [
            'service_id'  => 'required|string|max:200',
            'api_key'     => 'required|string|max:500',
            'secret_key'  => 'required|string|max:500',
            'public_key'  => 'nullable|string|max:5000',
            'certificate' => 'nullable|string|max:10000',
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'Service ID is required.',
            'api_key.required'    => 'API Key is required.',
            'secret_key.required' => 'Secret Key is required.',
        ];
    }
}
