<?php
return [
    'auto_provision' => (bool) env('SUPERADMIN_AUTO_PROVISION', false),
    'name' => env('SUPERADMIN_NAME', 'Super Administrator'),
    'email' => env('SUPERADMIN_EMAIL'),
    'password' => env('SUPERADMIN_PASSWORD'),
];
