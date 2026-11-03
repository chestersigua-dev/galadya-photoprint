<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'package_addon_id',
        'qty',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected $appends = [
        'formatted_subtotal',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function packageAddon(): BelongsTo
    {
        return $this->belongsTo(PackageAddon::class, 'package_addon_id');
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return '₱' . number_format($this->subtotal, 2);
    }
}

