<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engage_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('public_key', 64)->unique();
            $table->string('name')->default('Website');
            $table->json('allowed_origins')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });

        Schema::create('engage_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('engage_site_id')->constrained('engage_sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 32);
            $table->string('status', 16)->default('draft');
            $table->unsignedInteger('priority')->default(0);
            $table->json('content')->nullable();
            $table->json('targeting')->nullable();
            $table->json('style')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['engage_site_id', 'status', 'priority']);
        });

        Schema::create('engage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('engage_campaigns')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('path', 2048)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'campaign_id', 'type']);
            $table->index(['campaign_id', 'created_at']);
        });

        Schema::create('engage_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('engage_campaigns')->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->json('payload')->nullable();
            $table->string('page_url', 2048)->nullable();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['campaign_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engage_leads');
        Schema::dropIfExists('engage_events');
        Schema::dropIfExists('engage_campaigns');
        Schema::dropIfExists('engage_sites');
    }
};
