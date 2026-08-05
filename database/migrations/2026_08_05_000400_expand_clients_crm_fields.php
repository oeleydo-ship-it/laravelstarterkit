<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('company')->nullable()->after('name');
            $table->string('status')->default('lead')->after('phone');
            $table->json('tags')->nullable()->after('status');
            $table->string('website')->nullable()->after('tags');
            $table->string('source')->nullable()->after('website');
            $table->string('address')->nullable()->after('source');
            $table->string('city')->nullable()->after('address');
            $table->string('country')->nullable()->after('city');
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('client_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_notes');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn([
                'company',
                'status',
                'tags',
                'website',
                'source',
                'address',
                'city',
                'country',
            ]);
        });
    }
};
