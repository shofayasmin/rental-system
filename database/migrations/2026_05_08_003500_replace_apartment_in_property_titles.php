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
        DB::statement("UPDATE properties SET title = REPLACE(title, 'Apartment', 'House')");
        DB::statement("UPDATE properties SET title = REPLACE(title, 'apartment', 'house')");
        DB::statement("UPDATE properties SET title = REPLACE(title, 'APARTMENT', 'HOUSE')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE properties SET title = REPLACE(title, 'House', 'Apartment')");
        DB::statement("UPDATE properties SET title = REPLACE(title, 'house', 'apartment')");
        DB::statement("UPDATE properties SET title = REPLACE(title, 'HOUSE', 'APARTMENT')");
    }
};

