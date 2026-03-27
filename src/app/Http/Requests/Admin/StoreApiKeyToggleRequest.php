<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiKeyToggleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認可は Controller で Policy に委ねる
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
