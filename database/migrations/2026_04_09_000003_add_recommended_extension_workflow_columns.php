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
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('monthly_rent', 12, 2)->nullable()->after('total_price');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('type', ['initial_rent', 'extension_rent'])
                ->default('initial_rent')
                ->after('amount');
            $table->foreignId('contract_extension_id')
                ->nullable()
                ->after('agent_id')
                ->constrained('contract_extensions')
                ->nullOnDelete();
        });

        Schema::table('contract_extensions', function (Blueprint $table) {
            $table->unsignedInteger('months_requested')
                ->default(1)
                ->after('contract_id');
            $table->decimal('monthly_rent_snapshot', 12, 2)
                ->nullable()
                ->after('months_requested');
            $table->decimal('amount', 12, 2)
                ->nullable()
                ->after('monthly_rent_snapshot');
            $table->enum('status', ['pending', 'awaiting_payment', 'paid', 'rejected', 'expired'])
                ->default('pending')
                ->after('amount');
            $table->foreignId('approved_by')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->timestamp('paid_at')->nullable()->after('rejected_at');
        });

        // backfill existing records
        DB::table('contracts')
            ->whereNull('monthly_rent')
            ->update(['monthly_rent' => DB::raw('total_price')]);

        DB::table('contract_extensions')
            ->whereNotNull('extended_at')
            ->update([
                'months_requested' => 1,
                'status' => 'paid',
                'paid_at' => DB::raw('extended_at'),
            ]);

        DB::statement("
            UPDATE contract_extensions ce
            JOIN contracts c ON c.id = ce.contract_id
            SET ce.monthly_rent_snapshot = c.monthly_rent
            WHERE ce.monthly_rent_snapshot IS NULL
        ");

        DB::statement("
            UPDATE contract_extensions
            SET amount = monthly_rent_snapshot * months_requested
            WHERE amount IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_extensions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'months_requested',
                'monthly_rent_snapshot',
                'amount',
                'status',
                'approved_by',
                'approved_at',
                'rejected_at',
                'paid_at',
            ]);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['contract_extension_id']);
            $table->dropColumn(['type', 'contract_extension_id']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('monthly_rent');
        });
    }
};
