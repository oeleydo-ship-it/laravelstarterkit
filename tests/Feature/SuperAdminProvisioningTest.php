<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
class SuperAdminProvisioningTest extends TestCase {
 use RefreshDatabase;
 public function test_command_provisions_a_superadmin_without_a_tenant():void{
  $this->artisan('app:provision-superadmin',['--email'=>'admin@example.com','--name'=>'Platform Admin','--password'=>'StrongPassword!123'])->assertSuccessful();
  $user=User::withoutGlobalScopes()->where('email','admin@example.com')->firstOrFail();
  $this->assertTrue($user->is_superadmin);$this->assertNull($user->tenant_id);$this->assertNull($user->role);$this->assertTrue(Hash::check('StrongPassword!123',$user->password));
 }
 public function test_reprovisioning_is_idempotent_and_password_rotation_is_explicit():void{
  $this->artisan('app:provision-superadmin',['--email'=>'admin@example.com','--password'=>'OriginalPassword!1'])->assertSuccessful();
  $this->artisan('app:provision-superadmin',['--email'=>'admin@example.com','--password'=>'IgnoredPassword!2'])->assertSuccessful();
  $user=User::withoutGlobalScopes()->where('email','admin@example.com')->firstOrFail();$this->assertTrue(Hash::check('OriginalPassword!1',$user->password));$this->assertSame(1,User::withoutGlobalScopes()->where('email','admin@example.com')->count());
  $this->artisan('app:provision-superadmin',['--email'=>'admin@example.com','--password'=>'RotatedPassword!3','--rotate-password'=>true])->assertSuccessful();
  $this->assertTrue(Hash::check('RotatedPassword!3',$user->fresh()->password));
 }
 public function test_command_rejects_an_unsafe_password():void{
  $this->artisan('app:provision-superadmin',['--email'=>'admin@example.com','--password'=>'short'])->assertFailed();
  $this->assertDatabaseMissing('users',['email'=>'admin@example.com']);
 }
}
