<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'layout')) {
                $table->dropColumn('layout');
            }

            if (Schema::hasColumn('properties', 'region')) {
                $table->dropColumn('region');
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'layout')) {
                $table->string('layout')->nullable()->after('bathrooms');
            }

            if (!Schema::hasColumn('properties', 'region')) {
                $table->string('region')->nullable()->after('title');
            }
        });
    }
};

