<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id'      => ['required', 'integer', 'exists:books,id'],
            'quantity'     => ['required', 'integer', 'min:1'],
            'purchased_at' => ['required', 'date'],
            'note'         => ['nullable', 'string', 'max:1000'],
            // store_id はログインユーザーから導出するため入力値を受け取らない
            // store_user_id はログインユーザーから導出するため入力値を受け取らない
        ];
    }
}
