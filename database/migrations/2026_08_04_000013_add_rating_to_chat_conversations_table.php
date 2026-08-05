<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('closed_at');
            $table->string('rating_comment', 1000)->nullable()->after('rating');
            // Separate from `rating` so "asked and answered" stays distinguishable
            // from "never rated" even if a score is ever cleared.
            $table->timestamp('rated_at')->nullable()->after('rating_comment');

            $table->index(['tenant_id', 'rated_at'], 'chat_conversations_rated_index');
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex('chat_conversations_rated_index');
            $table->dropColumn(['rating', 'rating_comment', 'rated_at']);
        });
    }
};
