<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認可は Controller で Policy に委ねる
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'allowed_ips'   => ['nullable', 'array'],
            'allowed_ips.*' => ['ip'],
            'expires_at'    => ['nullable', 'date', 'after:now'],
        ];
    }
}
