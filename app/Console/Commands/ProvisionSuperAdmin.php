<?php
namespace App\Console\Commands;
use App\Services\SuperAdminProvisioner;
use Illuminate\Console\Command;
use Throwable;
class ProvisionSuperAdmin extends Command {
 protected $signature='app:provision-superadmin {--email=} {--name=} {--password=} {--rotate-password}';
 protected $description='Idempotently provision the deployment super administrator';
 public function handle(SuperAdminProvisioner $provisioner):int{
  $email=$this->option('email')?:config('superadmin.email');$name=$this->option('name')?:config('superadmin.name');$password=$this->option('password')?:config('superadmin.password');
  try{$user=$provisioner->provision((string)$name,(string)$email,(string)$password,(bool)$this->option('rotate-password'));}
  catch(Throwable $e){$this->error($e->getMessage());return self::FAILURE;}
  $this->info("Superadmin provisioned: {$user->email}");return self::SUCCESS;
 }
}
