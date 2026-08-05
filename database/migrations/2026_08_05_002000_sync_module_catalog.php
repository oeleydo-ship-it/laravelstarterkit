<?php

use App\Support\ModuleCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        ModuleCatalog::sync();
    }

    public function down(): void
    {
        // Catalog rows are shared across tenants; leave them in place on rollback.
    }
};
