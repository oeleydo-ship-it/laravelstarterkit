<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_visitors', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('company')->nullable()->after('phone');
            $table->text('crm_notes')->nullable()->after('company');
            $table->string('ip_address', 45)->nullable()->after('crm_notes');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('location')->nullable()->after('user_agent');
            $table->string('country', 100)->nullable()->after('location');
            $table->string('city', 100)->nullable()->after('country');
            $table->string('current_page', 2048)->nullable()->after('city');
            $table->string('page_title')->nullable()->after('current_page');
            $table->json('page_visits')->nullable()->after('page_title');
        });
    }

    public function down(): void
    {
        Schema::table('chat_visitors', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'company',
                'crm_notes',
                'ip_address',
                'user_agent',
                'location',
                'country',
                'city',
                'current_page',
                'page_title',
                'page_visits',
            ]);
        });
    }
};
