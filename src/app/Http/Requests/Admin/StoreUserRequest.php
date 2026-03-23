<?php

namespace App\Http\Requests\Admin;

use App\Models\StoreUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $storeUser = $this->route('store_user');

        return [
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required',
                'email',
                'max:255',
                Rule::unique(StoreUser::class, 'email')->ignore($storeUser?->id),
            ],
            'password' => [$storeUser ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', Rule::in(['owner', 'employee'])],
        ];
    }
}
