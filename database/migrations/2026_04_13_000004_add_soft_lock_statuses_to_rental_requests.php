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
            ALTER TABLE rental_requests
            MODIFY status ENUM(
                'pending',
                'approved',
                'rejected',
                'queued',
                'awaiting_payment',
                'paid',
                'cancelled_by_tenant',
                'cancelled_by_agent',
                'cancelled_lost'
            ) NOT NULL DEFAULT 'pending'
        ");

        Schema::table('rental_requests', function (Blueprint $table) {
            $table->timestamp('queued_at')->nullable()->after('status');
            $table->timestamp('awaiting_payment_at')->nullable()->after('queued_at');
            $table->timestamp('paid_at')->nullable()->after('awaiting_payment_at');
            $table->timestamp('rejected_at')->nullable()->after('paid_at');
            $table->timestamp('cancelled_at')->nullable()->after('rejected_at');
        });

        // Backfill existing records from previous flow.
        DB::statement("
            UPDATE rental_requests rr
            LEFT JOIN transactions tx
                ON tx.rental_request_id = rr.id
                AND tx.type = 'initial_rent'
            SET rr.status = CASE
                WHEN rr.status = 'approved' AND tx.status = 'paid' THEN 'paid'
                WHEN rr.status = 'approved' AND (tx.status IS NULL OR tx.status = 'unpaid' OR tx.status = 'failed') THEN 'awaiting_payment'
                ELSE rr.status
            END
        ");

        DB::statement("
            UPDATE rental_requests
            SET rejected_at = COALESCE(rejected_at, updated_at)
            WHERE status = 'rejected'
        ");

        DB::statement("
            UPDATE rental_requests
            SET paid_at = COALESCE(paid_at, updated_at)
            WHERE status = 'paid'
        ");

        DB::statement("
            UPDATE rental_requests
            SET awaiting_payment_at = COALESCE(awaiting_payment_at, updated_at)
            WHERE status = 'awaiting_payment'
        ");

        DB::statement("
            ALTER TABLE rental_requests
            MODIFY status ENUM(
                'pending',
                'queued',
                'awaiting_payment',
                'paid',
                'rejected',
                'cancelled_by_tenant',
                'cancelled_by_agent',
                'cancelled_lost'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            UPDATE rental_requests
            SET status = CASE
                WHEN status IN ('awaiting_payment', 'paid') THEN 'approved'
                WHEN status IN ('queued', 'cancelled_by_tenant', 'cancelled_by_agent', 'cancelled_lost') THEN 'pending'
                ELSE status
            END
        ");

        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropColumn([
                'queued_at',
                'awaiting_payment_at',
                'paid_at',
                'rejected_at',
                'cancelled_at',
            ]);
        });

        DB::statement("
            ALTER TABLE rental_requests
            MODIFY status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'
        ");
    }
};
