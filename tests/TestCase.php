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
        $this->app->detectEnvironment(fn () => 'testing');
        config([
            'app.env' => 'testing',
            'database.default' => 'mysql',
            'database.connections.mysql.host' => getenv('DB_HOST') ?: '127.0.0.1',
            'database.connections.mysql.port' => getenv('DB_PORT') ?: '3307',
            'database.connections.mysql.database' => getenv('DB_DATABASE') ?: 'gagandeep_steels_test',
            'database.connections.mysql.username' => getenv('DB_USERNAME') ?: 'crm_local',
            'database.connections.mysql.password' => getenv('DB_PASSWORD') ?: 'LocalCRM-2026-Hostinger',
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        $database = (string) config('database.connections.mysql.database');
        if (config('database.default') !== 'mysql' || !str_ends_with($database, '_test')) {
            throw new \RuntimeException('Tests require a disposable MySQL database ending in _test. Configure .env.testing first.');
        }
        // Guard above runs BEFORE destructive test-only schema recreation.
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
    }
}
