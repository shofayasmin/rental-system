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
        DB::statement("
            UPDATE rental_requests
            SET status = 'pending_review'
            WHERE status = 'queued'
        ");

        DB::statement("
            ALTER TABLE rental_requests
            MODIFY status ENUM(
                'pending_review',
                'awaiting_payment',
                'paid',
                'rejected',
                'cancelled_by_tenant',
                'cancelled_by_agent',
                'cancelled_lost'
            ) NOT NULL DEFAULT 'pending_review'
        ");

        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropColumn('queued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->timestamp('queued_at')->nullable()->after('status');
        });

        DB::statement("
            ALTER TABLE rental_requests
            MODIFY status ENUM(
                'pending_review',
                'queued',
                'awaiting_payment',
                'paid',
                'rejected',
                'cancelled_by_tenant',
                'cancelled_by_agent',
                'cancelled_lost'
            ) NOT NULL DEFAULT 'pending_review'
        ");
    }
};
