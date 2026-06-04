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
        $this->deduplicateContractsByRentalRequest();
        $this->deduplicateTransactionsByRequestAndType();

        Schema::table('contracts', function (Blueprint $table) {
            $table->unique('rental_request_id', 'contracts_rental_request_unique');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unique(['rental_request_id', 'type'], 'transactions_request_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_request_type_unique');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropUnique('contracts_rental_request_unique');
        });
    }

    private function deduplicateContractsByRentalRequest(): void
    {
        $duplicateRequestIds = DB::table('contracts')
            ->select('rental_request_id')
            ->groupBy('rental_request_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('rental_request_id');

        foreach ($duplicateRequestIds as $rentalRequestId) {
            $contracts = DB::table('contracts')
                ->where('rental_request_id', $rentalRequestId)
                ->orderByRaw("CASE status WHEN 'active' THEN 2 WHEN 'ended' THEN 1 ELSE 0 END DESC")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get(['id']);

            $keeperId = $contracts->first()?->id;
            if (!$keeperId) {
                continue;
            }

            $redundantIds = $contracts->skip(1)->pluck('id')->all();
            if (empty($redundantIds)) {
                continue;
            }

            DB::table('contract_extensions')
                ->whereIn('contract_id', $redundantIds)
                ->update(['contract_id' => $keeperId]);

            DB::table('contracts')
                ->whereIn('id', $redundantIds)
                ->delete();
        }
    }

    private function deduplicateTransactionsByRequestAndType(): void
    {
        $duplicateGroups = DB::table('transactions')
            ->select('rental_request_id', 'type')
            ->groupBy('rental_request_id', 'type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $transactions = DB::table('transactions')
                ->where('rental_request_id', $group->rental_request_id)
                ->where('type', $group->type)
                ->orderByRaw('contract_extension_id IS NULL ASC')
                ->orderByRaw("CASE status WHEN 'paid' THEN 3 WHEN 'unpaid' THEN 2 WHEN 'failed' THEN 1 ELSE 0 END DESC")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get(['id']);

            $keeperId = $transactions->first()?->id;
            if (!$keeperId) {
                continue;
            }

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
