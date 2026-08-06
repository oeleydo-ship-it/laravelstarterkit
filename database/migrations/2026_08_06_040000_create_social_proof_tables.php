<?php

use App\Support\ModuleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('social_proof_sites')) {
            Schema::create('social_proof_sites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('public_key', 64)->unique();
                $table->string('name')->default('Website');
                $table->json('allowed_origins')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('social_proof_events')) {
            Schema::create('social_proof_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('social_proof_site_id')->constrained()->cascadeOnDelete();
                $table->string('type', 16)->default('purchase'); // purchase | subscribe
                $table->string('source', 16)->default('fake'); // fake | live | api
                $table->string('customer_name');
                $table->string('location')->nullable();
                $table->string('item_name');
                $table->string('avatar_url')->nullable();
                $table->string('product_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('occurred_at')->nullable();
                $table->timestamps();
                // MySQL identifier limit is 64 chars — keep custom names short.
                $table->index(['social_proof_site_id', 'is_active', 'occurred_at'], 'sp_events_site_active_occurred_idx');
                $table->index(['tenant_id', 'type', 'source'], 'sp_events_tenant_type_source_idx');
            });
        }

        ModuleCatalog::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('social_proof_events');
        Schema::dropIfExists('social_proof_sites');
    }
};
