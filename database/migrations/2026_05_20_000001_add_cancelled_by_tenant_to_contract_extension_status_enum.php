<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE contract_extensions
            MODIFY status ENUM('pending', 'awaiting_payment', 'paid', 'rejected', 'cancelled_by_tenant', 'expired')
            NOT NULL DEFAULT 'pending'
        ");

        DB::table('contract_extensions')
            ->where('status', 'rejected')
            ->whereNotNull('approved_by')
            ->update([
                'status' => 'cancelled_by_tenant',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('contract_extensions')
            ->where('status', 'cancelled_by_tenant')
            ->update([
                'status' => 'rejected',
            ]);

        DB::statement("
            ALTER TABLE contract_extensions
            MODIFY status ENUM('pending', 'awaiting_payment', 'paid', 'rejected', 'expired')
            NOT NULL DEFAULT 'pending'
        ");
    }
};

