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
        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('province_id')
                ->nullable()
                ->after('title')
                ->constrained('provinces')
                ->nullOnDelete();
            $table->foreignId('regency_id')
                ->nullable()
                ->after('province_id')
                ->constrained('regencies')
                ->nullOnDelete();
            $table->foreignId('district_id')
                ->nullable()
                ->after('regency_id')
                ->constrained('districts')
                ->nullOnDelete();
            $table->foreignId('village_id')
                ->nullable()
                ->after('district_id')
                ->constrained('villages')
                ->nullOnDelete();

            $table->unsignedTinyInteger('bedrooms')
                ->default(0)
                ->after('address');
            $table->decimal('bathrooms', 3, 1)
                ->default(1.0)
                ->after('bedrooms');

            $table->index('status');
            $table->index('rent_price');
            $table->index('bedrooms');
            $table->index('bathrooms');
            $table->index(
                ['province_id', 'regency_id', 'district_id', 'village_id'],
                'properties_location_hierarchy_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('properties_location_hierarchy_idx');
            $table->dropIndex(['status']);
            $table->dropIndex(['rent_price']);
            $table->dropIndex(['bedrooms']);
            $table->dropIndex(['bathrooms']);

            $table->dropForeign(['province_id']);
            $table->dropForeign(['regency_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['village_id']);

            $table->dropColumn([
                'province_id',
                'regency_id',
                'district_id',
                'village_id',
                'bedrooms',
                'bathrooms',
            ]);
        });
    }
};
