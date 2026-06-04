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
        Schema::table('contracts', function (Blueprint $table) {
            $table->text('ended_reason')->nullable()->after('status');
            $table->foreignId('ended_by')
                ->nullable()
                ->after('ended_reason')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('ended_at')->nullable()->after('ended_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['ended_by']);
            $table->dropColumn(['ended_reason', 'ended_by', 'ended_at']);
        });
    }
};
