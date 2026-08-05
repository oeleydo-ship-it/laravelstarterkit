<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes matching the queries the module actually runs. The original chat
 * tables only indexed `tenant_id`, which stops helping as soon as the inbox
 * filters by status and orders by activity, or a report scans a date range.
 *
 * The existing single-column `tenant_id` indexes are left in place: they are
 * redundant prefixes of these composites, but dropping them buys nothing and
 * would need per-driver index-name handling.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            // Inbox: filter by status, order by last activity.
            $table->index(['tenant_id', 'status', 'last_message_at'], 'chat_conversations_inbox_index');
            // "Assigned to me", and the routing service's per-agent load counts.
            $table->index(['tenant_id', 'assigned_to'], 'chat_conversations_assignee_index');
            // Reports, which scan a date range of conversations.
            $table->index(['tenant_id', 'created_at'], 'chat_conversations_created_index');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            // Unread counters: visitor messages in a thread with no read_at.
            $table->index(['chat_conversation_id', 'sender_type', 'read_at'], 'chat_messages_unread_index');
            // The visitor-visible transcript, which always excludes internal notes.
            $table->index(['chat_conversation_id', 'is_internal'], 'chat_messages_visible_index');
            // Reports, which aggregate messages over a date range.
            $table->index(['tenant_id', 'created_at'], 'chat_messages_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex('chat_conversations_inbox_index');
            $table->dropIndex('chat_conversations_assignee_index');
            $table->dropIndex('chat_conversations_created_index');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex('chat_messages_unread_index');
            $table->dropIndex('chat_messages_visible_index');
            $table->dropIndex('chat_messages_created_index');
        });
    }
};
