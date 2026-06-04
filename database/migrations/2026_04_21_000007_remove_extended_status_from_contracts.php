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
        DB::table('contracts')
            ->where('status', 'extended')
            ->update(['status' => 'active']);

        DB::statement("
            ALTER TABLE contracts
            MODIFY status ENUM('active', 'ended') NOT NULL DEFAULT 'active'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE contracts
            MODIFY status ENUM('active', 'extended', 'ended') NOT NULL DEFAULT 'active'
        ");
    }
};
