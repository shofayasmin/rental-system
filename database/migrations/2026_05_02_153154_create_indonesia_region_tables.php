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
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->timestamps();
        });

        Schema::create('regencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')
                ->constrained('provinces')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->timestamps();

            $table->index('province_id');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regency_id')
                ->constrained('regencies')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->timestamps();

            $table->index('regency_id');
        });

        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')
                ->constrained('districts')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->timestamps();

            $table->index('district_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
};
