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
        $this->deduplicateTransactionsByContractExtension();

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('rental_request_id', 'transactions_rental_request_id_idx');
            $table->dropUnique('transactions_request_type_unique');
            $table->unique('contract_extension_id', 'transactions_contract_extension_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_contract_extension_unique');
            $table->unique(['rental_request_id', 'type'], 'transactions_request_type_unique');
            $table->dropIndex('transactions_rental_request_id_idx');
        });
    }

    private function deduplicateTransactionsByContractExtension(): void
    {
        $duplicateExtensionIds = DB::table('transactions')
            ->whereNotNull('contract_extension_id')
            ->select('contract_extension_id')
            ->groupBy('contract_extension_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('contract_extension_id');

        foreach ($duplicateExtensionIds as $extensionId) {
            $transactions = DB::table('transactions')
                ->where('contract_extension_id', $extensionId)
                ->orderByRaw("CASE status WHEN 'paid' THEN 3 WHEN 'unpaid' THEN 2 WHEN 'failed' THEN 1 ELSE 0 END DESC")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get(['id']);

            $redundantIds = $transactions->skip(1)->pluck('id')->all();
            if (empty($redundantIds)) {
                continue;
            }

            DB::table('transactions')
                ->whereIn('id', $redundantIds)
                ->delete();
        }
    }
};
