<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('color', 20)->default('#40358e');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('task_statuses')->insert([
            ['name'=>'New','color'=>'#08798b','position'=>1,'is_completed'=>false,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'In Feedback','color'=>'#40358e','position'=>2,'is_completed'=>false,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Completed','color'=>'#397400','position'=>3,'is_completed'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Rejected','color'=>'#ed0027','position'=>4,'is_completed'=>false,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Processing','color'=>'#40358e','position'=>5,'is_completed'=>false,'created_at'=>$now,'updated_at'=>$now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('task_statuses');
    }
};
