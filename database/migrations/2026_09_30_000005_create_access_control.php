<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\Permissions;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name', 100)->unique(); $table->string('description', 255)->nullable();
            $table->boolean('is_admin')->default(false); $table->json('permissions');
            $table->unsignedInteger('version')->default(1); $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name', 100); $table->string('email')->unique(); $table->string('password');
            $table->foreignId('role_id')->constrained()->restrictOnDelete(); $table->boolean('active')->default(true);
            $table->unsignedInteger('session_version')->default(1); $table->unsignedInteger('version')->default(1);
            $table->timestamp('last_login_at')->nullable(); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); $table->string('token'); $table->timestamp('created_at')->nullable();
        });
        $business = array_values(array_filter(Permissions::keys(), fn ($key) => str_starts_with($key, 'rates.') || str_starts_with($key, 'transporters.')));
        foreach ([
            ['Administrator', 'Full CRM and account administration. Protected system role.', true, Permissions::keys()],
            ['Manager', 'Manage freight rates and transporters, including exports and deletion.', false, $business],
            ['Operator', 'Add and update business records without deleting or exporting.', false, ['rates.view','rates.create','rates.edit','transporters.view','transporters.create','transporters.edit']],
            ['Viewer', 'Read-only access to freight rates and transporters.', false, ['rates.view','transporters.view']],
        ] as [$name, $description, $admin, $permissions]) {
            DB::table('roles')->insert(['name' => $name, 'description' => $description, 'is_admin' => $admin, 'permissions' => json_encode($permissions), 'created_at' => now(), 'updated_at' => now()]);
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens'); Schema::dropIfExists('users'); Schema::dropIfExists('roles');
    }
};
