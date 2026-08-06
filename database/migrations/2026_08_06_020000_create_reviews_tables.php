<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('public_key', 64)->unique();
            $table->string('name')->default('Website');
            $table->json('allowed_origins')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_site_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->string('author_name');
            $table->string('author_company')->nullable();
            $table->string('author_avatar')->nullable();
            $table->string('status', 16)->default('pending');
            $table->string('source', 64)->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['review_site_id', 'status', 'rating']);
        });

        Schema::create('review_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('layout', 16)->default('stacked');
            $table->unsignedTinyInteger('min_rating')->default(1);
            $table->unsignedSmallInteger('max_items')->default(6);
            $table->json('style')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamps();
            $table->index(['review_site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_widgets');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('review_sites');
    }
};
