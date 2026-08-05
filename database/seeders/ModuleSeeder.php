<?php

namespace Database\Seeders;

use App\Support\ModuleCatalog;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        ModuleCatalog::sync();
    }
}
