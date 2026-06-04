<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contract_extensions', function (Blueprint $table) {
            $table->timestamp('payment_due_at')
                ->nullable()
                ->after('approved_at');

            $table->index('payment_due_at', 'contract_extensions_payment_due_at_idx');
        });

        DB::statement("
            UPDATE contract_extensions
            SET payment_due_at = DATE_ADD(approved_at, INTERVAL 7 DAY)
            WHERE status = 'awaiting_payment'
              AND approved_at IS NOT NULL
              AND payment_due_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_extensions', function (Blueprint $table) {
            $table->dropIndex('contract_extensions_payment_due_at_idx');
            $table->dropColumn('payment_due_at');
        });
    }
};
