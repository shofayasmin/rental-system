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
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->timestamp('payment_due_at')
                ->nullable()
                ->after('awaiting_payment_at');

            $table->index('payment_due_at', 'rental_requests_payment_due_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropIndex('rental_requests_payment_due_at_idx');
            $table->dropColumn('payment_due_at');
        });
    }
};
