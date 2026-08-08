<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::table('autoblog_destinations',function(Blueprint $table){$table->timestamp('verified_at')->nullable()->after('is_active');$table->text('verification_error')->nullable()->after('verified_at');});}
 public function down():void{Schema::table('autoblog_destinations',fn(Blueprint $table)=>$table->dropColumn(['verified_at','verification_error']));}
};
