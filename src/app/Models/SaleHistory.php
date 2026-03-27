<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'book_id',
        'quantity',
        'sold_at',
        'pos_terminal_id',
    ];

    protected $casts = [
        'sold_at'  => 'datetime',
        'quantity' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
