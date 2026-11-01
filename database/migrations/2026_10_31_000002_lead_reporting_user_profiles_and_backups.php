<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 30)->nullable()->unique()->after('email');
            $table->string('avatar_path', 500)->nullable()->after('phone');
        });

        Schema::create('lead_source_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->string('source', 100)->default('Manual')->index();
            $table->timestamp('received_at')->index();
            $table->timestamp('lost_at')->nullable()->index();
            $table->timestamp('deleted_at')->nullable()->index();
            $table->timestamps();
            $table->unique('lead_id');
        });

        Schema::create('lead_backup_settings', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('backup_after_days')->default(30);
            $table->unsignedInteger('delete_after_days')->default(365);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
        DB::table('lead_backup_settings')->insert(['id' => 1, 'backup_after_days' => 30, 'delete_after_days' => 365, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('lead_backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable()->unique();
            $table->string('source', 100)->default('Manual')->index();
            $table->string('status', 40)->default('Backed up')->index();
            $table->json('snapshot');
            $table->timestamp('backed_up_at')->index();
            $table->timestamp('purge_at')->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('leads')->orderBy('id')->chunkById(500, function ($leads) use ($now) {
            $rows = [];
            foreach ($leads as $lead) $rows[] = [
                'lead_id' => $lead->id,
                'source' => $lead->source ?: 'Manual',
                'received_at' => $lead->created_at,
                'lost_at' => $lead->stage === 'Lost' ? ($lead->updated_at ?: $lead->created_at) : null,
                'deleted_at' => $lead->deleted_at,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($rows) DB::table('lead_source_events')->insert($rows);
        }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_backups');
        Schema::dropIfExists('lead_backup_settings');
        Schema::dropIfExists('lead_source_events');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'avatar_path']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
