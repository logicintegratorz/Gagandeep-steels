<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transporters', function (Blueprint $table) {
            // Nullable so transporters imported from existing rates remain valid.
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('gst_number', 15)->nullable();
            $table->string('address', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transporters', fn (Blueprint $table) =>
            $table->dropColumn(['contact_person', 'phone', 'gst_number', 'address']));
    }
};
