<?php
namespace App\Services;
use App\Models\User;
use InvalidArgumentException;
class SuperAdminProvisioner {
 public function provision(string $name,string $email,string $password,bool $rotatePassword=false): User {
  $email=strtolower(trim($email));
  if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('A valid superadmin email is required.');
  if(strlen($password)<12)throw new InvalidArgumentException('The superadmin password must contain at least 12 characters.');
  $user=User::withoutGlobalScopes()->firstOrNew(['email'=>$email]);$new=!$user->exists;
  $user->fill(['name'=>$name,'tenant_id'=>null,'role'=>null,'status'=>'active','is_superadmin'=>true,'email_verified_at'=>$user->email_verified_at??now()]);
  if($new||$rotatePassword)$user->password=$password;
  $user->save();
  return $user;
 }
}
