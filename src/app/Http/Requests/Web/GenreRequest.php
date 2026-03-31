<?php

namespace App\Http\Requests\Web;

use App\Models\Genre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $genre = $this->route('genre');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique(Genre::class, 'name')->ignore($genre?->id),
            ],
        ];
    }
}
