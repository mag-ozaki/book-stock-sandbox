<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'jan_code'   => $this->jan_code,
            'title'      => $this->title,
            'author'     => $this->author,
            'publisher'  => $this->publisher,
            'price'      => $this->price,
            'genre_id'   => $this->genre_id,
            'genre_name' => $this->genre?->name,
        ];
    }
}
