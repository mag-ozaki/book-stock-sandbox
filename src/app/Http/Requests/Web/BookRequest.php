<?php

namespace App\Http\Requests\Web;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'isbn'      => [
                'nullable',
                'string',
                'max:20',
                Rule::unique(Book::class, 'isbn')->ignore($book?->id),
            ],
            'title'     => ['required', 'string', 'max:255'],
            'author'    => ['required', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'price'     => ['nullable', 'integer', 'min:0'],
        ];
    }
}
