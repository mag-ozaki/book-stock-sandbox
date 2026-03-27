<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SaleHistoryRequest extends FormRequest
{
    /**
     * 認可は AuthenticateStoreApiKey Middleware で完結しているため true を返す。
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jan_code'        => ['required', 'string', 'digits:26'],
            'quantity'        => ['required', 'integer', 'min:1', 'max:9999'],
            'sold_at'         => ['nullable', 'date'],
            'pos_terminal_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
