<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('autoblog_destinations', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->string('type'); $table->string('base_url');
            $table->string('username')->nullable(); $table->text('secret')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
            $table->index(['tenant_id', 'is_active']);
        });
        Schema::create('autoblog_posts', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained('autoblog_destinations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('topic'); $table->string('title')->nullable(); $table->string('slug')->nullable();
            $table->text('excerpt')->nullable(); $table->longText('content')->nullable();
            $table->string('status')->default('draft'); $table->string('provider')->nullable();
            $table->string('external_id')->nullable(); $table->text('external_url')->nullable();
            $table->text('last_error')->nullable(); $table->timestamp('published_at')->nullable(); $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('autoblog_posts'); Schema::dropIfExists('autoblog_destinations'); }
};
