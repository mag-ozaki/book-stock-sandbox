<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'jan_code',
        'title',
        'author',
        'publisher',
        'price',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function purchaseHistories(): HasMany
    {
        return $this->hasMany(PurchaseHistory::class);
    }

    public function saleHistories(): HasMany
    {
        return $this->hasMany(SaleHistory::class);
    }
}
