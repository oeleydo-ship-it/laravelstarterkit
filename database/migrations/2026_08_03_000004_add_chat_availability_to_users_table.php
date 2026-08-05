<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('chat_availability')->default('offline')->after('status'); // online, away, offline
            $table->timestamp('chat_last_seen_at')->nullable()->after('chat_availability');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['chat_availability', 'chat_last_seen_at']);
        });
    }
};
