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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('region')->nullable();
            $table->string('address')->nullable();
            $table->string('layout')->nullable();
            $table->float('area')->nullable();
            $table->json('facilities')->nullable();
            $table->decimal('rent_price', 12, 2);
            $table->enum('status', ['to-let','rented','maintenance'])->default('to-let');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
