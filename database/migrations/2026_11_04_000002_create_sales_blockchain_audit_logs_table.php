<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_blockchain_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('block_index')->unique()->index();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('transaction_code', 50)->nullable()->index();
            $table->string('event_type', 50)->index(); // 'system.genesis', 'sale.committed', 'sale.accessed', 'sale.receipt_printed', 'ledger.verified'
            $table->json('payload');
            $table->char('previous_hash', 64)->index();
            $table->char('hash', 64)->unique()->index();
            $table->unsignedBigInteger('nonce')->default(0);
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('operator_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('timestamp', 40)->index(); // Exact ISO-8601 string used in SHA-256 digest
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_blockchain_logs');
    }
};

