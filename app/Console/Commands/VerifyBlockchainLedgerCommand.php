<?php

namespace App\Console\Commands;

use App\Services\BlockchainAuditService;
use Illuminate\Console\Command;

class VerifyBlockchainLedgerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:verify-ledger {--sync : Automatically mine unrecorded historical sales first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cryptographically verify the integrity of the Galadya Photoprint sales blockchain ledger';

    /**
     * Execute the console command.
     */
    public function handle(BlockchainAuditService $auditService): int
    {
        $this->info('Initializing Galadya Photoprint Cryptographic Ledger Audit...');

        if ($this->option('sync')) {
            $this->comment('Synchronizing historical transactions into blockchain ledger...');
            $synced = $auditService->syncHistoricalTransactions();
            $this->info("Successfully synchronized {$synced} historical sales blocks.");
        }

        $result = $auditService->verifyChainIntegrity();

        if ($result['is_valid']) {
            $this->newLine();
            $this->info('================================================================');
            $this->info(' [PASS] SALES BLOCKCHAIN LEDGER IS 100% SOUND & TAMPER-FREE');
            $this->info('================================================================');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Blocks Verified', $result['total_blocks']],
                    ['Latest Block Index', '#' . ($result['latest_block_index'] ?? 0)],
                    ['Genesis Hash', $result['genesis_hash'] ?? 'N/A'],
                    ['Latest Block Hash', $result['latest_block_hash'] ?? 'N/A'],
                    ['Total Audited Revenue', '₱' . number_format($result['total_audited_revenue'] ?? 0, 2)],
                    ['Verification Timestamp', $result['verified_at']],
                    ['Status', 'CRYPTOGRAPHICALLY VALID'],
                ]
            );
            $this->newLine();
            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('================================================================');
        $this->error(' [ALERT] CRYPTOGRAPHIC TAMPERING DETECTED IN SALES LEDGER!');
        $this->error('================================================================');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Failed Block Index', '#' . ($result['failed_block_index'] ?? 'Unknown')],
                ['Error Type', $result['error_type'] ?? 'TAMPERED'],
                ['Details', $result['message'] ?? 'Mismatch detected'],
                ['Total Blocks in Chain', $result['total_blocks'] ?? 0],
                ['Verified Blocks Before Failure', $result['verified_blocks'] ?? 0],
                ['Timestamp', $result['verified_at']],
                ['Status', 'INTEGRITY COMPROMISED'],
            ]
        );
        $this->newLine();

        return self::FAILURE;
    }
}

