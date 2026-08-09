<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('chat_conversation_id')->nullable()->after('tenant_id')->constrained('chat_conversations')->nullOnDelete();
            $table->string('requester_name')->nullable()->after('description');
            $table->string('requester_email')->nullable()->after('requester_name');
            $table->string('category')->nullable()->after('requester_email');
            $table->string('source')->default('agent')->after('category');
            $table->timestamp('resolved_at')->nullable()->after('assigned_to');
            $table->index(['tenant_id', 'status', 'priority']);
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['chat_conversation_id']);
            $table->dropIndex(['tenant_id', 'status', 'priority']);
            $table->dropColumn(['chat_conversation_id', 'requester_name', 'requester_email', 'category', 'source', 'resolved_at']);
        });
    }
};
