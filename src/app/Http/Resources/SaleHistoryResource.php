<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'store_id'        => $this->store_id,
            'book'            => [
                'id'       => $this->book->id,
                'title'    => $this->book->title,
                'jan_code' => $this->book->jan_code,
            ],
            'quantity'        => $this->quantity,
            'sold_at'         => $this->sold_at,
            'pos_terminal_id' => $this->pos_terminal_id,
        ];
    }
}
