<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'formatted_price',
    ];

    public function transactionAddons(): HasMany
    {
        return $this->hasMany(TransactionAddon::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return '₱' . number_format($this->price, 2);
    }
}

