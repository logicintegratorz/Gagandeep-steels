<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        $names = [
            'Rolling Mill / Manufacturer', 'Finish Goods Godown', 'MS Melting Units',
            'Cast Iron Foundries', 'Cast Iron Wires', 'Cast Iron Sellers', 'Scrap Suppliers',
        ];
        $existing = DB::table('leads')->whereNotNull('job_role')->distinct()->pluck('job_role')->all();
        foreach (array_unique(array_merge($names, $existing)) as $name) {
            DB::table('job_roles')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_roles');
    }
};
