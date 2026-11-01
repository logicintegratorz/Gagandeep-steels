<?php
namespace Tests\Feature;

use App\Models\{Lead, Role, User};
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ResetTestingDataTest extends TestCase
{
    public function test_reset_clears_crm_records_but_keeps_administrator_login(): void
    {
        $admin = $this->signIn();
        $password = $admin->password;
        $otherRole = Role::where('is_admin', false)->firstOrFail();
        User::create(['name' => 'Testing user', 'email' => 'user@example.test', 'password' => 'Password1234', 'role_id' => $otherRole->id]);
        Lead::create(['name' => 'Temporary inquiry', 'stage' => 'New', 'owner_id' => $admin->id]);
        DB::table('freight_rates')->insert(['transporter' => 'Temporary', 'origin' => 'A', 'destination' => 'B', 'price' => 100]);

        $this->artisan('crm:reset-testing-data')->assertSuccessful();

        $this->assertSame($password, $admin->fresh()->password);
        $this->assertSame($admin->email, $admin->fresh()->email);
        $this->assertSame(1, User::count());
        $this->assertSame(0, Lead::withTrashed()->count());
        $this->assertSame(0, DB::table('freight_rates')->count());
        $this->assertSame(7, DB::table('job_roles')->count());
        $this->assertSame(3, DB::table('lead_connections')->count());
    }
}
