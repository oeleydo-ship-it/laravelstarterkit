<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('autoblog_posts',function(Blueprint $table){$table->string('tone')->default('Professional')->after('topic');$table->string('keywords')->nullable()->after('tone');$table->timestamp('scheduled_at')->nullable()->after('last_error');$table->unsignedTinyInteger('attempt_count')->default(0)->after('scheduled_at');}); }
 public function down(): void { Schema::table('autoblog_posts',fn(Blueprint $table)=>$table->dropColumn(['tone','keywords','scheduled_at','attempt_count'])); }
};
