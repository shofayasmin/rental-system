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
            $table->timestamp('cancelled_at')->nullable()->after('rejected_at');
        });

        DB::table('contract_extensions')
            ->where('status', 'cancelled_by_tenant')
            ->whereNull('cancelled_at')
            ->update([
                'cancelled_at' => DB::raw('COALESCE(rejected_at, updated_at)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_extensions', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });
    }
};

