<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('conversation_messages', 'property_id')) {
            Schema::table('conversation_messages', function (Blueprint $table) {
                $table->foreignId('property_id')
                    ->nullable()
                    ->after('sender_id')
                    ->constrained('properties')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('conversation_messages', 'rental_request_id')) {
            Schema::table('conversation_messages', function (Blueprint $table) {
                $table->foreignId('rental_request_id')
                    ->nullable()
                    ->after('property_id')
                    ->constrained('rental_requests')
                    ->nullOnDelete();
            });
        }

        $cmHasIndex = collect(DB::select('SHOW INDEX FROM conversation_messages'))
            ->pluck('Key_name')
            ->contains('cm_conversation_created_idx');

        if (!$cmHasIndex) {
            Schema::table('conversation_messages', function (Blueprint $table) {
                $table->index(['conversation_id', 'created_at'], 'cm_conversation_created_idx');
            });
        }

        DB::table('conversation_messages as cm')
            ->join('conversations as c', 'c.id', '=', 'cm.conversation_id')
            ->whereNull('cm.property_id')
            ->update([
                'cm.property_id' => DB::raw('c.property_id'),
                'cm.rental_request_id' => DB::raw('c.rental_request_id'),
            ]);

        $duplicatePairs = DB::table('conversations')
            ->select('tenant_id', 'agent_id', DB::raw('COUNT(*) as total'))
            ->groupBy('tenant_id', 'agent_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicatePairs as $pair) {
            $rows = DB::table('conversations')
                ->where('tenant_id', $pair->tenant_id)
                ->where('agent_id', $pair->agent_id)
                ->orderByRaw('last_message_at IS NULL')
                ->orderByDesc('last_message_at')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get(['id', 'property_id', 'rental_request_id', 'last_message_at', 'updated_at']);

            $keeper = $rows->first();
            if (!$keeper) {
                continue;
            }

            $duplicateIds = $rows->skip(1)->pluck('id')->all();
            if (empty($duplicateIds)) {
                continue;
            }

            DB::table('conversation_messages')
                ->whereIn('conversation_id', $duplicateIds)
                ->update(['conversation_id' => $keeper->id]);

            $latestRentalRequestId = $rows
                ->pluck('rental_request_id')
                ->filter()
                ->first();

            $latestPropertyId = DB::table('conversation_messages')
                ->where('conversation_id', $keeper->id)
                ->whereNotNull('property_id')
                ->orderByDesc('id')
                ->value('property_id') ?? $keeper->property_id;

            $latestMessageAt = DB::table('conversation_messages')
                ->where('conversation_id', $keeper->id)
                ->max('created_at');

            DB::table('conversations')
                ->where('id', $keeper->id)
                ->update([
                    'property_id' => $latestPropertyId,
                    'rental_request_id' => $latestRentalRequestId,
                    'last_message_at' => $latestMessageAt,
                    'updated_at' => now(),
                ]);

            DB::table('conversations')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }

        $conversationIndexNames = collect(DB::select('SHOW INDEX FROM conversations'))
            ->pluck('Key_name')
            ->unique();

        if ($conversationIndexNames->contains('conversations_property_id_tenant_id_agent_id_unique')) {
            $hasPropertyStandaloneIndex = $conversationIndexNames->contains('conversations_property_id_foreign');

            if (!$hasPropertyStandaloneIndex) {
                Schema::table('conversations', function (Blueprint $table) {
                    $table->index('property_id', 'conversations_property_id_foreign');
                });
            }

            DB::statement('ALTER TABLE conversations DROP INDEX conversations_property_id_tenant_id_agent_id_unique');
        }

        $conversationIndexNames = collect(DB::select('SHOW INDEX FROM conversations'))
            ->pluck('Key_name')
            ->unique();

        if (!$conversationIndexNames->contains('conversations_tenant_agent_unique')) {
            DB::statement('ALTER TABLE conversations ADD UNIQUE conversations_tenant_agent_unique (tenant_id, agent_id)');
        }
    }

    public function down(): void
    {
        $conversationIndexNames = collect(DB::select('SHOW INDEX FROM conversations'))
            ->pluck('Key_name')
            ->unique();

        if ($conversationIndexNames->contains('conversations_tenant_agent_unique')) {
            DB::statement('ALTER TABLE conversations DROP INDEX conversations_tenant_agent_unique');
        }

        $conversationIndexNames = collect(DB::select('SHOW INDEX FROM conversations'))
            ->pluck('Key_name')
            ->unique();

        if (!$conversationIndexNames->contains('conversations_property_id_tenant_id_agent_id_unique')) {
            DB::statement('ALTER TABLE conversations ADD UNIQUE conversations_property_id_tenant_id_agent_id_unique (property_id, tenant_id, agent_id)');
        }

        if (Schema::hasColumn('conversation_messages', 'rental_request_id')) {
            Schema::table('conversation_messages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('rental_request_id');
            });
        }

        if (Schema::hasColumn('conversation_messages', 'property_id')) {
            Schema::table('conversation_messages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('property_id');
            });
        }

        $cmHasIndex = collect(DB::select('SHOW INDEX FROM conversation_messages'))
            ->pluck('Key_name')
            ->contains('cm_conversation_created_idx');

        if ($cmHasIndex) {
            Schema::table('conversation_messages', function (Blueprint $table) {
                $table->dropIndex('cm_conversation_created_idx');
            });
        }
    }
};
