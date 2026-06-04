<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('messages');

        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropColumn('message');
        });
    }

    public function down(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('sender_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->text('message');
            $table->timestamps();
        });

        Schema::table('rental_requests', function (Blueprint $table) {
            $table->text('message')->nullable();
        });
    }
};
