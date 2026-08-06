<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_sites', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('public_key', 64)->unique(); $table->string('name');
            $table->json('allowed_origins')->nullable(); $table->json('settings')->nullable(); $table->timestamps();
        });
        Schema::create('forms', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_site_id')->constrained()->cascadeOnDelete(); $table->string('name');
            $table->string('type', 32); $table->string('status', 16)->default('draft');
            $table->json('fields'); $table->json('settings')->nullable(); $table->text('thank_you')->nullable(); $table->timestamps();
        });
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id(); $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete(); $table->string('email')->nullable();
            $table->string('name')->nullable(); $table->json('answers'); $table->string('page_url', 2000)->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('form_submissions'); Schema::dropIfExists('forms'); Schema::dropIfExists('form_sites'); }
};
