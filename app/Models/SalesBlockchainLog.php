<?php

namespace App\Models;

use App\Services\BlockchainAuditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesBlockchainLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sales_blockchain_logs';

    /**
     * Disable standard updated_at since blockchain logs are strictly append-only and immutable.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'block_index',
        'transaction_id',
        'transaction_code',
        'event_type',
        'payload',
        'previous_hash',
        'hash',
        'nonce',
        'operator_id',
        'operator_name',
        'ip_address',
        'user_agent',
        'timestamp',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'block_index' => 'integer',
            'payload' => 'array',
            'nonce' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Transaction associated with this block.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Operator user who initiated or authorized this block.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Truncated block hash accessor for clean UI pills.
     */
    public function getShortHashAttribute(): string
    {
        if (strlen($this->hash) < 16) {
            return $this->hash;
        }

        return substr($this->hash, 0, 8) . '...' . substr($this->hash, -6);
    }

    /**
     * Truncated previous hash accessor.
     */
    public function getShortPreviousHashAttribute(): string
    {
        if ($this->isGenesis()) {
            return '00000000...000000 (GENESIS)';
        }

        if (strlen($this->previous_hash) < 16) {
            return $this->previous_hash;
        }

        return substr($this->previous_hash, 0, 8) . '...' . substr($this->previous_hash, -6);
    }

    /**
     * Formatted timestamp in Philippine Standard Time.
     */
    public function getFormattedDateAttribute(): string
    {
        if ($this->created_at) {
            return $this->created_at->timezone('Asia/Manila')->format('d M Y, h:i:s A');
        }

        return Carbon::parse($this->timestamp)->timezone('Asia/Manila')->format('d M Y, h:i:s A');
    }

    /**
     * Check if this block is the genesis block.
     */
    public function isGenesis(): bool
    {
        return (int) $this->block_index === 1;
    }

    /**
     * Recalculate mathematical SHA-256 hash from record data.
     */
    public function calculateMathematicalHash(): string
    {
        $canonicalPayload = BlockchainAuditService::canonicalJson(
            is_array($this->payload) ? $this->payload : json_decode((string) $this->payload, true)
        );

        return BlockchainAuditService::calculateHash(
            (int) $this->block_index,
            (string) $this->previous_hash,
            (string) $this->timestamp,
            (string) $this->event_type,
            $canonicalPayload,
            (int) $this->nonce
        );
    }

    /**
     * Verify whether this individual block's stored hash matches its mathematical digest.
     */
    public function verifySelfIntegrity(): bool
    {
        return hash_equals($this->hash, $this->calculateMathematicalHash());
    }
}

