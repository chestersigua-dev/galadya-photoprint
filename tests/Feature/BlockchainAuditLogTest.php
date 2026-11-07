<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Package;
use App\Models\SalesBlockchainLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BlockchainAuditService;
use App\Services\TransactionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BlockchainAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_genesis_block_is_automatically_created_with_64_zeros(): void
    {
        $auditService = app(BlockchainAuditService::class);
        $genesis = $auditService->ensureGenesisBlock();

        $this->assertNotNull($genesis);
        $this->assertEquals(1, $genesis->block_index);
        $this->assertEquals(str_repeat('0', 64), $genesis->previous_hash);
        $this->assertEquals('system.genesis', $genesis->event_type);
        $this->assertEquals(64, strlen($genesis->hash));
        $this->assertTrue($genesis->verifySelfIntegrity());
    }

    public function test_sales_transaction_automatically_anchors_into_blockchain_ledger(): void
    {
        $admin = User::first();
        $customer = Customer::first();
        $package = Package::first();
        $txService = app(TransactionService::class);

        $initialBlocksCount = SalesBlockchainLog::count();

        $result = $txService->finalizeWorkflow([
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'addons' => [],
            'discount_amount' => 0.00,
            'payment_method' => 'cash',
            'payment_status' => 'completed',
            'rfid' => 'GP-RFID-TEST99',
            'rfid_scanned' => true,
            'photos' => [],
        ], $admin->id);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('blockchain_block', $result);
        $this->assertNotNull($result['blockchain_block']['hash']);

        // Assert new block was mined in database
        $this->assertEquals($initialBlocksCount + 1, SalesBlockchainLog::count());

        $latestBlock = SalesBlockchainLog::orderBy('block_index', 'desc')->first();
        $this->assertEquals('sale.committed', $latestBlock->event_type);
        $this->assertEquals($result['transaction']->transaction_code, $latestBlock->transaction_code);
        $this->assertTrue($latestBlock->verifySelfIntegrity());

        // Assert previous hash correctly points to predecessor
        $predecessor = SalesBlockchainLog::where('block_index', $latestBlock->block_index - 1)->first();
        $this->assertNotNull($predecessor);
        $this->assertEquals($predecessor->hash, $latestBlock->previous_hash);
    }

    public function test_access_logging_records_audit_blocks_for_views_and_receipts(): void
    {
        $admin = User::first();
        $transaction = Transaction::first();
        $auditService = app(BlockchainAuditService::class);

        $beforeCount = SalesBlockchainLog::count();

        // Access transaction details
        $this->actingAs($admin)
            ->getJson(route('transactions.show', $transaction->id))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals($beforeCount + 1, SalesBlockchainLog::count());
        $accessBlock = SalesBlockchainLog::orderBy('block_index', 'desc')->first();
        $this->assertEquals('sale.accessed', $accessBlock->event_type);
        $this->assertTrue($accessBlock->verifySelfIntegrity());

        // Print thermal receipt
        $this->actingAs($admin)
            ->get(route('transactions.receipt', $transaction->id))
            ->assertStatus(200);

        $this->assertEquals($beforeCount + 2, SalesBlockchainLog::count());
        $receiptBlock = SalesBlockchainLog::orderBy('block_index', 'desc')->first();
        $this->assertEquals('sale.receipt_printed', $receiptBlock->event_type);
        $this->assertTrue($receiptBlock->verifySelfIntegrity());
        $this->assertEquals($accessBlock->hash, $receiptBlock->previous_hash);
    }

    public function test_blockchain_chain_integrity_verification_succeeds_when_untampered(): void
    {
        $auditService = app(BlockchainAuditService::class);
        $verification = $auditService->verifyChainIntegrity();

        $this->assertTrue($verification['is_valid']);
        $this->assertEquals('verified', $verification['status']);
        $this->assertGreaterThan(0, $verification['total_blocks']);
        $this->assertEquals($verification['total_blocks'], $verification['verified_blocks']);
    }

    public function test_blockchain_detects_data_tampering_and_identifies_exact_block(): void
    {
        $auditService = app(BlockchainAuditService::class);

        // Pick block #2 (a sale block)
        $block = SalesBlockchainLog::where('block_index', 2)->first();
        $this->assertNotNull($block);

        // Tamper with the block's payload directly in the database
        $hackedPayload = $block->payload;
        $hackedPayload['financials']['grand_total'] = 999999.00;

        DB::table('sales_blockchain_logs')
            ->where('id', $block->id)
            ->update([
                'payload' => json_encode($hackedPayload),
            ]);

        // Run integrity verification
        $verification = $auditService->verifyChainIntegrity();

        $this->assertFalse($verification['is_valid']);
        $this->assertEquals('tampered', $verification['status']);
        $this->assertEquals('HASH_DIGEST_MISMATCH', $verification['error_type']);
        $this->assertEquals(2, $verification['failed_block_index']);
        $this->assertStringContainsString('Block #2', $verification['message']);
    }

    public function test_blockchain_detects_broken_chain_linkage_tampering(): void
    {
        $auditService = app(BlockchainAuditService::class);

        // Tamper with previous_hash pointer of block #3
        $block = SalesBlockchainLog::where('block_index', 3)->first();
        if (! $block) {
            $this->markTestSkipped('Block #3 not found in seed data.');
        }

        DB::table('sales_blockchain_logs')
            ->where('id', $block->id)
            ->update([
                'previous_hash' => str_repeat('a', 64),
            ]);

        $verification = $auditService->verifyChainIntegrity();

        $this->assertFalse($verification['is_valid']);
        $this->assertEquals('tampered', $verification['status']);
        $this->assertEquals('BROKEN_CHAIN_LINK', $verification['error_type']);
        $this->assertEquals(3, $verification['failed_block_index']);
    }

    public function test_admin_can_view_blockchain_ledger_explorer_ui(): void
    {
        $admin = User::first();

        $response = $this->actingAs($admin)
            ->get(route('admin.audit.index'));

        $response->assertStatus(200);
        $response->assertSee('Sales Blockchain Ledger & Access Audit');
        $response->assertSee('SHA-256 Chained');
        $response->assertSee('Verify Ledger Integrity');
    }

    public function test_admin_can_trigger_ledger_verification_via_api(): void
    {
        $admin = User::first();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.audit.verify'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'result' => [
                'is_valid' => true,
                'status' => 'verified',
            ],
        ]);
    }

    public function test_inspect_single_block_api(): void
    {
        $admin = User::first();
        $block = SalesBlockchainLog::first();

        $response = $this->actingAs($admin)
            ->getJson(route('admin.audit.show', $block->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_valid' => true,
            'block' => [
                'id' => $block->id,
                'block_index' => $block->block_index,
            ],
        ]);
    }

    public function test_artisan_command_verify_ledger_succeeds_and_fails_on_tamper(): void
    {
        // Standard ledger audit check
        $this->artisan('audit:verify-ledger')
            ->assertExitCode(0);

        // Simulate database tampering
        $block = SalesBlockchainLog::orderBy('block_index', 'desc')->first();
        DB::table('sales_blockchain_logs')
            ->where('id', $block->id)
            ->update([
                'hash' => str_repeat('f', 64),
            ]);

        // Audit command detects discrepancy
        $this->artisan('audit:verify-ledger')
            ->assertExitCode(1);
    }
}
