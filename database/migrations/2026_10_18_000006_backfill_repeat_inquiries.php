<?php

use App\Models\Lead;
use App\Support\LeadIdentity;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
 public function up():void {
  Lead::withTrashed()->orderBy('id')->chunkById(100,function($leads){
   foreach($leads as $lead)LeadIdentity::attachPrevious($lead);
  });
 }
 public function down():void {}
};
