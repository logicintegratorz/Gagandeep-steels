<?php
namespace Tests\Feature;

use App\Models\{Role, User};
use Illuminate\Support\Facades\{Hash, Password};
use Tests\TestCase;

final class AdminRecoveryTest extends TestCase
{
    private function viewer(): User
    {
        return User::create(['name' => 'Dispatch user', 'email' => 'dispatch@example.test', 'password' => 'OriginalPassword123', 'active' => true, 'role_id' => Role::where('name', 'Viewer')->firstOrFail()->id]);
    }
    private function updateData(User $user, array $overrides = []): array
    {
        return array_merge(['name' => $user->name, 'email' => $user->email, 'active' => 1, 'version' => $user->fresh()->version], $overrides);
    }
    public function test_admin_can_reset_password_and_revoke_tokens_and_sessions(): void
    {
        $this->signIn(); $user = $this->viewer(); $token = Password::createToken($user);
        $this->get(route('users.edit', $user))->assertOk()->assertSee('New password (optional)')->assertDontSee($user->password);
        $this->put(route('users.update', $user), $this->updateData($user, ['password' => 'NewPassword12345', 'password_confirmation' => 'NewPassword12345']))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertTrue(Hash::check('NewPassword12345', $user->fresh()->password));
        $this->assertSame(2, $user->fresh()->session_version);
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->signIn($user->fresh())->refresh();
        $this->withSession(['session_version' => 1])->get(route('rates.index'))->assertRedirect(route('login'));
    }
    public function test_blank_password_is_preserved_and_invalid_confirmation_is_rejected(): void
    {
        $this->signIn(); $user = $this->viewer(); $hash = $user->password;
        $this->put(route('users.update', $user), $this->updateData($user, ['password' => '', 'password_confirmation' => '']))->assertSessionHasNoErrors();
        $this->assertSame($hash, $user->fresh()->password);
        $this->put(route('users.update', $user), $this->updateData($user, ['password' => 'UpdatedPassword123', 'password_confirmation' => 'Mismatch12345']))->assertSessionHasErrors('password');
        $this->assertSame($hash, $user->fresh()->password);
    }
    public function test_non_admin_cannot_reset_password_even_with_legacy_reset_permission(): void
    {
        $role = Role::create(['name' => 'Delegated accounts', 'permissions' => ['users.view', 'users.edit', 'users.reset_password', 'rates.view', 'transporters.view']]);
        $manager = User::create(['name' => 'Manager', 'email' => 'manager@example.test', 'password' => 'SecurePassword123', 'active' => true, 'role_id' => $role->id]);
        $this->signIn($manager); $user = $this->viewer();
        $this->get(route('users.edit', $user))->assertOk()->assertDontSee('New password (optional)')->assertDontSee('Send reset link');
        $this->put(route('users.update', $user), $this->updateData($user, ['password' => 'Unauthorized12345', 'password_confirmation' => 'Unauthorized12345']))->assertForbidden();
        $this->post('/users/'.$user->id.'/reset-password')->assertNotFound();
        $this->assertTrue(Hash::check('OriginalPassword123', $user->fresh()->password));
    }
    public function test_admin_editing_own_credentials_returns_to_login_with_message(): void
    {
        $admin = $this->signIn();
        $this->put(route('users.update', $admin), $this->updateData($admin, ['email' => 'changed@example.test', 'password' => 'UpdatedPassword123', 'password_confirmation' => 'UpdatedPassword123']))->assertRedirect(route('login'))->assertSessionHas('status');
        $this->assertGuest();
        $this->post(route('login.store'), ['email' => 'changed@example.test', 'password' => 'UpdatedPassword123'])->assertRedirect(route('home'));
    }
    public function test_terminal_recovery_lists_admin_email_and_resets_without_email_service(): void
    {
        $admin = $this->signIn(); $admin->update(['active' => false]);
        $token = Password::createToken($admin);
        $this->artisan('crm:recover-admin')
            ->expectsTable(['ID', 'Name', 'Login email', 'Status'], [[$admin->id, $admin->name, $admin->email, 'Inactive']])
            ->expectsQuestion('Administrator ID to recover (blank to cancel)', (string) $admin->id)
            ->expectsQuestion('Login email (press Enter to keep current)', 'recovered@example.test')
            ->expectsQuestion('New password (8+ characters, letters and numbers)', 'RecoveredPassword123')
            ->expectsQuestion('Confirm new password', 'RecoveredPassword123')
            ->expectsConfirmation('Reset this administrator and activate their account? All existing sessions will end.', 'yes')
            ->assertSuccessful();
        $this->assertTrue(Hash::check('RecoveredPassword123', $admin->fresh()->password));
        $this->assertSame('recovered@example.test', $admin->fresh()->email);
        $this->assertTrue($admin->fresh()->active);
        $this->assertSame(2, $admin->fresh()->session_version);
        $this->assertFalse(Password::tokenExists($admin, $token));
    }
    public function test_terminal_recovery_rejects_non_admin_selection(): void
    {
        $this->signIn(); $user = $this->viewer();
        $this->artisan('crm:recover-admin')->expectsQuestion('Administrator ID to recover (blank to cancel)', (string) $user->id)->assertFailed();
        $this->assertTrue(Hash::check('OriginalPassword123', $user->fresh()->password));
    }
}
