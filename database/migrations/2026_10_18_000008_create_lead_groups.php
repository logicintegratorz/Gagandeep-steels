<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up():void {
        Schema::create('lead_groups',function(Blueprint $table){
            $table->id();$table->foreignId('created_by')->constrained('users');$table->string('name',100);$table->timestamps();
            $table->unique(['created_by','name']);
        });
        Schema::create('lead_group_members',function(Blueprint $table){
            $table->foreignId('lead_group_id')->constrained('lead_groups')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();$table->timestamps();
            $table->primary(['lead_group_id','lead_id']);
        });
    }
    public function down():void {Schema::dropIfExists('lead_group_members');Schema::dropIfExists('lead_groups');}
};
