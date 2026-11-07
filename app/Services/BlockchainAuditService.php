<?php

namespace App\Services;

use App\Models\SalesBlockchainLog;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class BlockchainAuditService
{
    /**
     * Canonical 64-character zero string for the Genesis block predecessor.
     */
    public const GENESIS_PREVIOUS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Ensure the Genesis block (#1) exists in the ledger.
     */
    public function ensureGenesisBlock(): SalesBlockchainLog
    {
        $existing = SalesBlockchainLog::where('block_index', 1)->first();
        if ($existing) {
            return $existing;
        }

        $timestamp = Carbon::now('Asia/Manila')->toIso8601String();
        $payload = [
            'protocol' => 'Galadya Photoprint Cryptographic Sales Ledger v1.0',
            'network' => 'Galadya Studio Mainnet',
            'currency' => 'PHP',
            'hash_algorithm' => 'SHA-256',
            'established' => '2026-01-01',
            'message' => 'Genesis Block - Immutable Sales & Financial Access Audit Trail Initiated.',
            'timestamp' => $timestamp,
        ];

        $canonicalPayload = self::canonicalJson($payload);
        $hash = self::calculateHash(
            1,
            self::GENESIS_PREVIOUS_HASH,
            $timestamp,
            'system.genesis',
            $canonicalPayload,
            0
        );

        return SalesBlockchainLog::create([
            'block_index' => 1,
            'transaction_id' => null,
            'transaction_code' => 'GENESIS-000001',
            'event_type' => 'system.genesis',
            'payload' => $payload,
            'previous_hash' => self::GENESIS_PREVIOUS_HASH,
            'hash' => $hash,
            'nonce' => 0,
            'operator_id' => auth()->id() ?? null,
            'operator_name' => auth()->user()?->name ?? 'System Architect',
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'Galadya-Console/1.0',
            'timestamp' => $timestamp,
            'created_at' => Carbon::parse($timestamp),
        ]);
    }

    /**
     * Mine and record a new sales transaction block into the immutable ledger.
     */
    public function recordSaleBlock(
        Transaction $transaction,
        string $eventType = 'sale.committed',
        array $extraMeta = []
    ): SalesBlockchainLog {
        return DB::transaction(function () use ($transaction, $eventType, $extraMeta) {
            $this->ensureGenesisBlock();

            // Lock the latest block row to prevent race conditions during concurrent checkouts
            $lastBlock = SalesBlockchainLog::lockForUpdate()
                ->orderBy('block_index', 'desc')
                ->first();

            $newIndex = $lastBlock ? ((int) $lastBlock->block_index + 1) : 1;
            $previousHash = $lastBlock ? $lastBlock->hash : self::GENESIS_PREVIOUS_HASH;

            $timestamp = Carbon::now('Asia/Manila')->toIso8601String();
            $user = auth()->user();
            $operatorId = $user?->id ?? $transaction->created_by;
            $operatorName = $user?->name ?? $transaction->creator?->name ?? 'System Staff';

            // Ensure relations are loaded for comprehensive snapshot
            $transaction->loadMissing(['customer', 'package', 'addons.packageAddon', 'photos']);

            $addonsSnapshot = $transaction->addons->map(fn ($addon) => [
                'addon_id' => $addon->package_addon_id,
                'title' => $addon->packageAddon?->title ?? 'Custom Add-on',
                'qty' => (int) $addon->qty,
                'unit_price' => (float) $addon->unit_price,
                'subtotal' => (float) $addon->subtotal,
            ])->values()->toArray();

            $payload = [
                'transaction_id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'customer' => [
                    'id' => $transaction->customer_id,
                    'full_name' => $transaction->customer?->full_name,
                    'contact_number' => $transaction->customer?->contact_number,
                    'rfid' => $transaction->customer?->rfid,
                    'points_balance' => (int) ($transaction->customer?->points ?? 0),
                ],
                'financials' => [
                    'package_id' => $transaction->package_id,
                    'package_title' => $transaction->package?->title,
                    'package_price' => (float) $transaction->package_price,
                    'addons' => $addonsSnapshot,
                    'addons_total' => (float) $transaction->addons_total,
                    'discount_amount' => (float) $transaction->discount_amount,
                    'grand_total' => (float) $transaction->grand_total,
                    'payment_method' => $transaction->payment_method,
                    'payment_status' => $transaction->payment_status,
                ],
                'loyalty_rfid' => [
                    'rfid_scanned' => (bool) $transaction->rfid_scanned,
                    'rfid_code' => $transaction->rfid_code,
                    'points_earned' => (int) $transaction->points_earned,
                ],
                'audit' => [
                    'operator_id' => $operatorId,
                    'operator_name' => $operatorName,
                    'photos_count' => $transaction->photos->count(),
                    'notes' => $transaction->notes,
                    'meta' => $extraMeta,
                ],
                'timestamp' => $timestamp,
            ];

            $canonicalPayload = self::canonicalJson($payload);
            $nonce = 0;
            $hash = self::calculateHash(
                $newIndex,
                $previousHash,
                $timestamp,
                $eventType,
                $canonicalPayload,
                $nonce
            );

            return SalesBlockchainLog::create([
                'block_index' => $newIndex,
                'transaction_id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'event_type' => $eventType,
                'payload' => $payload,
                'previous_hash' => $previousHash,
                'hash' => $hash,
                'nonce' => $nonce,
                'operator_id' => $operatorId,
                'operator_name' => $operatorName,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent() ?? 'Galadya-Web/1.0',
                'timestamp' => $timestamp,
                'created_at' => Carbon::parse($timestamp),
            ]);
        });
    }

    /**
     * Record an access log block (e.g. viewing sale details, printing receipt).
     */
    public function recordAccessBlock(
        Transaction $transaction,
        string $eventType = 'sale.accessed',
        ?string $reason = null,
        array $extraMeta = []
    ): SalesBlockchainLog {
        return DB::transaction(function () use ($transaction, $eventType, $reason, $extraMeta) {
            $this->ensureGenesisBlock();

            $lastBlock = SalesBlockchainLog::lockForUpdate()
                ->orderBy('block_index', 'desc')
                ->first();

            $newIndex = $lastBlock ? ((int) $lastBlock->block_index + 1) : 1;
            $previousHash = $lastBlock ? $lastBlock->hash : self::GENESIS_PREVIOUS_HASH;

            $timestamp = Carbon::now('Asia/Manila')->toIso8601String();
            $user = auth()->user();
            $operatorId = $user?->id ?? null;
            $operatorName = $user?->name ?? 'Staff / Guest';

            $payload = [
                'action' => $eventType,
                'reason' => $reason,
                'transaction_id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'customer_name' => $transaction->customer?->full_name,
                'grand_total' => (float) $transaction->grand_total,
                'payment_status' => $transaction->payment_status,
                'operator_id' => $operatorId,
                'operator_name' => $operatorName,
                'meta' => $extraMeta,
                'timestamp' => $timestamp,
            ];

            $canonicalPayload = self::canonicalJson($payload);
            $nonce = 0;
            $hash = self::calculateHash(
                $newIndex,
                $previousHash,
                $timestamp,
                $eventType,
                $canonicalPayload,
                $nonce
            );

            return SalesBlockchainLog::create([
                'block_index' => $newIndex,
                'transaction_id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'event_type' => $eventType,
                'payload' => $payload,
                'previous_hash' => $previousHash,
                'hash' => $hash,
                'nonce' => $nonce,
                'operator_id' => $operatorId,
                'operator_name' => $operatorName,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent() ?? 'Galadya-Web/1.0',
                'timestamp' => $timestamp,
                'created_at' => Carbon::parse($timestamp),
            ]);
        });
    }

    /**
     * Cryptographically verify the integrity of the entire blockchain ledger.
     * Detects any altered records, broken hash links, or sequence gaps.
     */
    public function verifyChainIntegrity(): array
    {
        $blocks = SalesBlockchainLog::orderBy('block_index', 'asc')->get();
        $totalBlocks = $blocks->count();

        if ($totalBlocks === 0) {
            return [
                'is_valid' => true,
                'status' => 'empty',
                'total_blocks' => 0,
                'verified_blocks' => 0,
                'message' => 'Blockchain ledger is initialized and empty.',
                'verified_at' => Carbon::now('Asia/Manila')->toIso8601String(),
            ];
        }

        $expectedPreviousHash = self::GENESIS_PREVIOUS_HASH;
        $totalAuditedRevenue = 0.00;
        $verifiedCount = 0;

        foreach ($blocks as $idx => $block) {
            $expectedIndex = $idx + 1;

            // Verify sequence index continuity
            if ((int) $block->block_index !== $expectedIndex) {
                return [
                    'is_valid' => false,
                    'status' => 'tampered',
                    'error_type' => 'SEQUENCE_GAP',
                    'failed_block_index' => $block->block_index,
                    'expected_index' => $expectedIndex,
                    'message' => "Sequence gap detected at Block #{$block->block_index}. Expected index was #{$expectedIndex}.",
                    'total_blocks' => $totalBlocks,
                    'verified_blocks' => $verifiedCount,
                    'verified_at' => Carbon::now('Asia/Manila')->toIso8601String(),
                ];
            }

            // Verify hash link to predecessor
            if ($block->previous_hash !== $expectedPreviousHash) {
                return [
                    'is_valid' => false,
                    'status' => 'tampered',
                    'error_type' => 'BROKEN_CHAIN_LINK',
                    'failed_block_index' => $block->block_index,
                    'stored_previous_hash' => $block->previous_hash,
                    'expected_previous_hash' => $expectedPreviousHash,
                    'message' => "Cryptographic chain linkage broken at Block #{$block->block_index}. Previous hash pointer does not match predecessor.",
                    'total_blocks' => $totalBlocks,
                    'verified_blocks' => $verifiedCount,
                    'verified_at' => Carbon::now('Asia/Manila')->toIso8601String(),
                ];
            }

            // Recompute SHA-256 digest from canonical payload
            $canonicalPayload = self::canonicalJson(
                is_array($block->payload) ? $block->payload : json_decode((string) $block->payload, true)
            );

            $recomputedHash = self::calculateHash(
                (int) $block->block_index,
                (string) $block->previous_hash,
                (string) $block->timestamp,
                (string) $block->event_type,
                $canonicalPayload,
                (int) $block->nonce
            );

            if (! hash_equals($block->hash, $recomputedHash)) {
                return [
                    'is_valid' => false,
                    'status' => 'tampered',
                    'error_type' => 'HASH_DIGEST_MISMATCH',
                    'failed_block_index' => $block->block_index,
                    'stored_hash' => $block->hash,
                    'recomputed_hash' => $recomputedHash,
                    'message' => "Data tampering detected in Block #{$block->block_index} ({$block->event_type}). Block hash does not match mathematical contents.",
                    'total_blocks' => $totalBlocks,
                    'verified_blocks' => $verifiedCount,
                    'verified_at' => Carbon::now('Asia/Manila')->toIso8601String(),
                ];
            }

            // Accumulate financial totals from confirmed sale blocks
            if ($block->event_type === 'sale.committed' && isset($block->payload['financials']['grand_total'])) {
                $totalAuditedRevenue += (float) $block->payload['financials']['grand_total'];
            }

            // Advance the link
            $expectedPreviousHash = $block->hash;
            $verifiedCount++;
        }

        $lastBlock = $blocks->last();
        $genesisBlock = $blocks->first();

        return [
            'is_valid' => true,
            'status' => 'verified',
            'total_blocks' => $totalBlocks,
            'verified_blocks' => $verifiedCount,
            'genesis_hash' => $genesisBlock?->hash,
            'latest_block_index' => $lastBlock?->block_index,
            'latest_block_hash' => $lastBlock?->hash,
            'total_audited_revenue' => $totalAuditedRevenue,
            'message' => "Cryptographic sales ledger is 100% verified. {$totalBlocks} blocks intact, tamper-free, and cryptographically sound.",
            'verified_at' => Carbon::now('Asia/Manila')->toIso8601String(),
        ];
    }

    /**
     * Synchronize any historical transactions not yet recorded on the blockchain ledger.
     */
    public function syncHistoricalTransactions(): int
    {
        $existingTxIds = SalesBlockchainLog::whereNotNull('transaction_id')
            ->where('event_type', 'sale.committed')
            ->pluck('transaction_id')
            ->toArray();

        $unrecordedTransactions = Transaction::whereNotIn('id', $existingTxIds)
            ->with(['customer', 'package', 'addons.packageAddon', 'photos'])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $syncedCount = 0;
        foreach ($unrecordedTransactions as $tx) {
            $this->recordSaleBlock($tx, 'sale.committed', ['source' => 'historical_sync']);
            $syncedCount++;
        }

        return $syncedCount;
    }

    /**
     * Get aggregate statistics for the Blockchain explorer dashboard.
     */
    public function getLedgerStats(): array
    {
        $this->ensureGenesisBlock();

        $totalBlocks = SalesBlockchainLog::count();
        $salesBlocksCount = SalesBlockchainLog::where('event_type', 'sale.committed')->count();
        $accessBlocksCount = SalesBlockchainLog::whereIn('event_type', ['sale.accessed', 'sale.receipt_printed'])->count();

        $latestBlock = SalesBlockchainLog::orderBy('block_index', 'desc')->first();
        $genesisBlock = SalesBlockchainLog::where('block_index', 1)->first();

        $verification = $this->verifyChainIntegrity();

        return [
            'total_blocks' => $totalBlocks,
            'sales_blocks_count' => $salesBlocksCount,
            'access_blocks_count' => $accessBlocksCount,
            'total_audited_revenue' => $verification['total_audited_revenue'] ?? 0.00,
            'latest_block' => $latestBlock,
            'genesis_block' => $genesisBlock,
            'verification' => $verification,
        ];
    }

    /**
     * Deterministically sort keys and format array into canonical JSON string.
     */
    public static function canonicalJson(array $data): string
    {
        self::ksortRecursive($data);

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Compute SHA-256 block hash.
     */
    public static function calculateHash(
        int $blockIndex,
        string $previousHash,
        string $timestamp,
        string $eventType,
        string $canonicalPayload,
        int $nonce = 0
    ): string {
        $raw = implode('|', [
            $blockIndex,
            $previousHash,
            $timestamp,
            $eventType,
            $canonicalPayload,
            $nonce,
        ]);

        return hash('sha256', $raw);
    }

    /**
     * Recursively sort array keys in ascending order.
     */
    private static function ksortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::ksortRecursive($value);
            }
        }
    }
}
