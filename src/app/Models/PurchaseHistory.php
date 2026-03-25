<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'store_user_id',
        'book_id',
        'quantity',
        'purchased_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'date',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function storeUser(): BelongsTo
    {
        return $this->belongsTo(StoreUser::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
