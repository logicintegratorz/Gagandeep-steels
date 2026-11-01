<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX transporters_name_lower_unique ON transporters (LOWER(name))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS transporters_name_lower_unique');
    }
};
