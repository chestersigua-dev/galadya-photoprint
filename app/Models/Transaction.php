<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_code',
        'customer_id',
        'package_id',
        'package_price',
        'addons_total',
        'discount_amount',
        'grand_total',
        'payment_method',
        'payment_status',
        'rfid_code',
        'rfid_scanned',
        'points_earned',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'package_price' => 'decimal:2',
        'addons_total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'rfid_scanned' => 'boolean',
        'points_earned' => 'integer',
    ];

    protected $appends = [
        'formatted_grand_total',
        'formatted_date',
    ];

    /**
     * The customer who booked this session.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    /**
     * The selected package.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Add-ons included in this transaction.
     */
    public function addons(): HasMany
    {
        return $this->hasMany(TransactionAddon::class);
    }

    /**
     * Photos uploaded/selected for this session.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(TransactionPhoto::class);
    }

    /**
     * Staff user who handled this transaction.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Blockchain audit logs for this transaction.
     */
    public function blockchainLogs(): HasMany
    {
        return $this->hasMany(SalesBlockchainLog::class, 'transaction_id');
    }

    /**
     * Get the initial sale block from the immutable ledger.
     */
    public function salesBlock(): ?SalesBlockchainLog
    {
        return $this->blockchainLogs()->where('event_type', 'sale.committed')->first();
    }

    public function getFormattedGrandTotalAttribute(): string
    {
        return '₱' . number_format($this->grand_total, 2);
    }

    public function getFormattedPackagePriceAttribute(): string
    {
        return '₱' . number_format($this->package_price, 2);
    }

    public function getFormattedAddonsTotalAttribute(): string
    {
        return '₱' . number_format($this->addons_total, 2);
    }

    public function getFormattedDiscountAmountAttribute(): string
    {
        return '₱' . number_format($this->discount_amount, 2);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at ? $this->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') : '';
    }

    /**
     * Helper to get the solo favorite photo model if any.
     */
    public function getFavoritePhotoAttribute(): ?TransactionPhoto
    {
        return $this->photos()->where('is_favorite', true)->first();
    }

    /**
     * Helper to get the starred photos (10 selection).
     */
    public function getStarredPhotosAttribute()
    {
        return $this->photos()->where('is_starred', true)->get();
    }
}

