<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::withoutGlobalScopes()->get();
foreach ($users as $u) {
    echo $u->email . ' | tenant_id=' . $u->tenant_id . ' | role=' . $u->role . ' | is_superadmin=' . $u->is_superadmin . PHP_EOL;
}

echo PHP_EOL . 'Tenants:' . PHP_EOL;
$tenants = App\Models\Tenant::all();
foreach ($tenants as $t) {
    echo 'id=' . $t->id . ' | name=' . $t->name . ' | plan_id=' . $t->plan_id . PHP_EOL;
}
