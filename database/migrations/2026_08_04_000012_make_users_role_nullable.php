<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Superadmins belong to no tenant, so they have no tenant role — `role` was
 * NOT NULL, which made every superadmin insert (including the one in
 * DemoSeeder) fail outright.
 *
 * The 'member' default is kept: an ordinary user created without an explicit
 * role should still land as a member, not as null.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->default('member')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->change();
        });
    }
};
