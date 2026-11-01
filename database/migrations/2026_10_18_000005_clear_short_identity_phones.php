<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
 public function up():void {DB::table('leads')->whereNotNull('identity_phone')->whereRaw('CHAR_LENGTH(identity_phone) < 7')->update(['identity_phone'=>null]);}
 public function down():void {}
};
