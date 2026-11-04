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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('rfid')->nullable()->unique()->after('last_name');
            $table->unsignedInteger('points')->default(0)->after('notes');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('rfid_code')->nullable()->after('payment_status');
            $table->boolean('rfid_scanned')->default(false)->after('rfid_code');
            $table->unsignedInteger('points_earned')->default(0)->after('rfid_scanned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['rfid', 'points']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['rfid_code', 'rfid_scanned', 'points_earned']);
        });
    }
};

