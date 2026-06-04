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
            ALTER TABLE rental_requests
            MODIFY status ENUM(
                'pending',
                'pending_review',
                'queued',
                'awaiting_payment',
                'paid',
                'rejected',
                'cancelled_by_tenant',
                'cancelled_by_agent',
                'cancelled_lost'
            ) NOT NULL DEFAULT 'pending'
        ");

        DB::statement("
            UPDATE rental_requests
            SET status = 'pending_review'
            WHERE status = 'pending'
        ");

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE rental_requests
            MODIFY status ENUM(
                'pending',
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

        DB::statement("
            UPDATE rental_requests
            SET status = 'pending'
            WHERE status = 'pending_review'
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
};
