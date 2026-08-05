<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_canned_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title');
            $table->string('shortcut')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'shortcut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_canned_responses');
    }
};
