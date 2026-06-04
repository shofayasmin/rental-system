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
            $table->foreignId('availability_cycle_id')
                ->nullable()
                ->after('property_id')
                ->constrained('property_availability_cycles')
                ->nullOnDelete();

            $table->index(
                ['property_id', 'availability_cycle_id'],
                'rental_requests_property_cycle_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropIndex('rental_requests_property_cycle_idx');
            $table->dropForeign(['availability_cycle_id']);
            $table->dropColumn('availability_cycle_id');
        });
    }
};
