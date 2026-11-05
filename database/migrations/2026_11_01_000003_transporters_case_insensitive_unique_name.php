<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The MySQL installation already has a unique name index under utf8mb4_unicode_ci.
        if (DB::getDriverName() === 'mysql') return;
        DB::statement('CREATE UNIQUE INDEX transporters_name_lower_unique ON transporters (LOWER(name))');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') return;
        DB::statement('DROP INDEX IF EXISTS transporters_name_lower_unique');
    }
};
