<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'contact_number',
        'address',
        'notes',
    ];

    protected $appends = [
        'full_name',
        'total_sessions_count',
        'lifetime_spend',
    ];

    /**
     * All transactions/sessions made by this customer.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->latest();
    }

    /**
     * Computed attribute: Full Name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Computed attribute: Total Sessions Count.
     */
    public function getTotalSessionsCountAttribute(): int
    {
        return $this->transactions()->count();
    }

    /**
     * Computed attribute: Lifetime Spend (PHP).
     */
    public function getLifetimeSpendAttribute(): float
    {
        return (float) $this->transactions()
            ->where('payment_status', 'completed')
            ->sum('grand_total');
    }

    /**
     * Formatted lifetime spend.
     */
    public function getFormattedLifetimeSpendAttribute(): string
    {
        return '₱' . number_format($this->lifetime_spend, 2);
    }
}

