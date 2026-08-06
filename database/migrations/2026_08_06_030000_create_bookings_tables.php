<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('public_key', 64)->unique();
            $table->string('name')->default('Bookings');
            $table->string('timezone', 64)->default('UTC');
            $table->json('allowed_origins')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        Schema::create('booking_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_site_id')->constrained('booking_sites')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->unsignedInteger('buffer_minutes')->default(0);
            $table->string('color', 7)->default('#0f766e');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['booking_site_id', 'active']);
        });

        Schema::create('booking_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_site_id')->constrained('booking_sites')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0=Sun .. 6=Sat
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
            $table->index(['booking_site_id', 'weekday']);
        });

        Schema::create('booking_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_site_id')->constrained('booking_sites')->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_closed')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
            $table->unique(['booking_site_id', 'date']);
        });

        Schema::create('booking_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_site_id')->constrained('booking_sites')->cascadeOnDelete();
            $table->foreignId('booking_service_id')->constrained('booking_services')->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('scheduled');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestamps();
            $table->index(['booking_site_id', 'starts_at']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_appointments');
        Schema::dropIfExists('booking_exceptions');
        Schema::dropIfExists('booking_availability');
        Schema::dropIfExists('booking_services');
        Schema::dropIfExists('booking_sites');
    }
};
