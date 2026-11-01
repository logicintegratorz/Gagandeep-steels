<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transporters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->timestamps();
        });
        // Retain every existing rate's transporter as an available choice.
        DB::table('freight_rates')->select('transporter')->distinct()->orderBy('transporter')
            ->chunk(200, function ($rates) {
                foreach ($rates as $rate) {
                    DB::table('transporters')->insertOrIgnore([
                        'name' => $rate->transporter, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void { Schema::dropIfExists('transporters'); }
};
