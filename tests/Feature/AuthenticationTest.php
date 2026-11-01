<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User, Role};
use Illuminate\Support\Facades\{Auth, Hash, Notification, Password};

final class AuthenticationTest extends TestCase
{
    private function account(array $overrides = []): User
    {
        return User::create(array_merge(['name'=>'Test User','email'=>'user@example.test','password'=>'SecurePassword123','role_id'=>Role::where('name','Viewer')->firstOrFail()->id,'active'=>true], $overrides));
    }
    public function test_guests_cannot_access_crm_and_login_has_brand_and_credit(): void
    {
        foreach (['rates.index','transporters.index','access.index'] as $route) $this->get(route($route))->assertRedirect(route('login'));
        $this->post(route('rates.store'), [])->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('gagandeep-steels-logo.png')->assertSee('Logic Integratorz')->assertDontSee('Forgot password?')->assertSee('js/ui.js')->assertSee('toast-region');
    }
    public function test_login_initializes_midnight_and_rejects_inactive_accounts(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-30 23:55:00', 'Asia/Kolkata'));
        $user = $this->account();
        $this->post(route('login.store'), ['email'=>$user->email,'password'=>'wrong'])->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email'=>$user->email,'password'=>'SecurePassword123'])->assertRedirect(route('home'))
            ->assertSessionHas('login_expires_at', \Carbon\Carbon::parse('2026-10-01 00:00:00','Asia/Kolkata')->timestamp);
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect(route('login')); $this->assertGuest();
        $user->update(['active'=>false]);
        $this->post(route('login.store'), ['email'=>$user->email,'password'=>'SecurePassword123'])->assertSessionHasErrors('email');
        $this->assertGuest(); $this->travelBack();
    }
    public function test_session_expires_at_midnight_even_with_recent_activity(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-30 23:59:40','Asia/Kolkata'));
        $this->signIn($this->account());
        $this->travel(20)->seconds();
        $this->getJson(route('session.status'))->assertUnauthorized(); $this->assertGuest(); $this->travelBack();
    }
    public function test_status_poll_does_not_extend_idle_and_activity_does(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-30 10:00:00','Asia/Kolkata'));
        $user = $this->signIn($this->account()); $initial = now()->timestamp;
        $this->travel(29)->minutes();
        $this->getJson(route('session.status'))->assertOk()->assertJsonPath('idleExpiresAt', $initial + 1800);
        $this->travel(1)->minutes();
        $this->getJson(route('session.status'))->assertUnauthorized(); $this->assertGuest();
        $this->signIn($user); $this->travel(29)->minutes();
        $this->postJson(route('session.activity'))->assertOk()->assertJsonPath('idleExpiresAt', now()->timestamp + 1800);
        $this->travel(2)->minutes(); $this->get(route('rates.index'))->assertOk(); $this->travelBack();
    }
    public function test_account_deactivation_and_password_version_revoke_sessions(): void
    {
        $user = $this->signIn($this->account());
        $user->increment('session_version');
        $this->get(route('rates.index'))->assertRedirect(route('login')); $this->assertGuest();
        $this->signIn($user->fresh()); $user->update(['active'=>false]);
        $this->getJson(route('session.status'))->assertUnauthorized();
    }
    public function test_public_password_recovery_is_unavailable(): void
    {
        Notification::fake(); $user = $this->account(); $token = Password::createToken($user);
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email'=>$user->email])->assertNotFound();
        $this->get('/reset-password/'.$token)->assertNotFound();
        $this->post('/reset-password', ['token'=>$token,'email'=>$user->email,'password'=>'UpdatedPassword123','password_confirmation'=>'UpdatedPassword123'])->assertNotFound();
        Notification::assertNothingSent();
        $this->assertTrue(Hash::check('SecurePassword123', $user->fresh()->password));
    }
    public function test_login_attempts_are_rate_limited(): void
    {
        $user = $this->account();
        for ($i=0; $i<5; $i++) $this->post(route('login.store'), ['email'=>$user->email,'password'=>'wrong'])->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email'=>$user->email,'password'=>'SecurePassword123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
    public function test_change_own_password_requires_current_password_and_keeps_session_limits(): void
    {
        $user = $this->signIn($this->account());
        $this->put(route('account.password'), ['current_password'=>'wrong','password'=>'NewPassword12345','password_confirmation'=>'NewPassword12345'])->assertSessionHasErrors('current_password');
        $this->put(route('account.password'), ['current_password'=>'SecurePassword123','password'=>'NewPassword12345','password_confirmation'=>'NewPassword12345'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword12345', $user->fresh()->password));
        $this->get(route('rates.index'))->assertOk();
    }
}
