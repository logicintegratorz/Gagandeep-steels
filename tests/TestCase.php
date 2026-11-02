<?php
namespace Tests;
use Illuminate\Foundation\Testing\TestCase as FrameworkTestCase;
abstract class TestCase extends FrameworkTestCase
{
    protected function signIn(?\App\Models\User $user = null): \App\Models\User
    {
        $user ??= \App\Models\User::create(['name' => 'Test Administrator', 'email' => 'admin@example.test', 'password' => 'TestPassword1234', 'role_id' => \App\Models\Role::where('is_admin', true)->firstOrFail()->id, 'active' => true]);
        $this->actingAs($user)->withSession(['last_activity_at' => now()->timestamp, 'login_expires_at' => now('Asia/Kolkata')->startOfDay()->addDay()->timestamp, 'session_version' => $user->session_version ?? 1]);
        return $user;
    }
    protected function setUp(): void
    {
        parent::setUp();
        // PHPUnit sets these values explicitly. Reapply them here so a locally
        // cached production config can never redirect destructive tests to the
        // application database.
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => getenv('DB_HOST') ?: '127.0.0.1',
            'database.connections.pgsql.port' => getenv('DB_PORT') ?: '5432',
            'database.connections.pgsql.database' => getenv('DB_DATABASE') ?: 'gagandeep_steels_test',
            'database.connections.pgsql.username' => getenv('DB_USERNAME') ?: 'gagandeep',
        ]);
        \Illuminate\Support\Facades\DB::purge('pgsql');
        $database = (string) config('database.connections.pgsql.database');
        if (config('database.default') !== 'pgsql' || !str_ends_with($database, '_test')) {
            throw new \RuntimeException('Tests require a disposable PostgreSQL database ending in _test. Configure .env.testing first.');
        }
        // Guard above runs BEFORE destructive test-only schema recreation.
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
    }
}
