<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_activity_lead', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_activity_id')->constrained('crm_activities')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['crm_activity_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activity_lead');
    }
};
