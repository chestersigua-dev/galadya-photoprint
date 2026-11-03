<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TransactionPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'original_filename',
        'storage_path',
        'preview_path',
        'is_starred',
        'is_favorite',
        'print_ready_path',
    ];

    protected $casts = [
        'is_starred' => 'boolean',
        'is_favorite' => 'boolean',
    ];

    protected $appends = [
        'preview_url',
        'storage_url',
        'print_ready_url',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getPreviewUrlAttribute(): ?string
    {
        if (!$this->preview_path) {
            return null;
        }

        return Storage::url($this->preview_path);
    }

    public function getStorageUrlAttribute(): ?string
    {
        if (!$this->storage_path) {
            return null;
        }

        return Storage::url($this->storage_path);
    }

    public function getPrintReadyUrlAttribute(): ?string
    {
        if (!$this->print_ready_path) {
            return null;
        }

        return Storage::url($this->print_ready_path);
    }
}

