<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'key_hash',
        'allowed_ips',
        'is_active',
        'last_used_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'store_id'     => 'integer',
        'allowed_ips'  => 'array',
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
