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
        Schema::create('property_availability_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete();
            $table->timestamp('available_from_at');
            $table->timestamp('unavailable_at')->nullable();
            $table->enum('closed_by', ['rented', 'maintenance', 'manual'])->nullable();
            $table->timestamps();

            $table->index(['property_id', 'available_from_at'], 'pac_property_available_idx');
            $table->index(['property_id', 'unavailable_at'], 'pac_property_unavailable_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_availability_cycles');
    }
};
