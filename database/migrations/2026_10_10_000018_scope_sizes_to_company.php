<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::table('product_sizes', fn(Blueprint $t) => $t->text('company')->nullable());
  Schema::table('product_size_activities', fn(Blueprint $t) => $t->text('company')->nullable());
  DB::transaction(function () {
   foreach(DB::table('product_sizes')->orderBy('id')->get() as $size) {
    $companies=DB::table('product_prices')->where('product_id',$size->product_id)->orderByDesc('effective_date')->orderByDesc('id')->get()->unique('company')->reject(fn($p)=>$p->removed)->pluck('company');
    if($companies->isEmpty()) $companies=collect(['']);
    foreach($companies->values() as $i=>$company) {
     if($i===0) {
      DB::table('product_sizes')->where('id',$size->id)->update(['company'=>$company ?? '']);
      DB::table('product_size_activities')->where('size_id',$size->id)->update(['company'=>$company ?? '']);
     } else {
      $copy=(array)$size; unset($copy['id']); $copy['company']=$company ?? '';
      DB::table('product_sizes')->insert($copy);
     }
    }
   }
  });
 }
 public function down(): void {
  Schema::table('product_sizes',fn(Blueprint $t)=>$t->dropColumn('company'));
  Schema::table('product_size_activities',fn(Blueprint $t)=>$t->dropColumn('company'));
 }
};
