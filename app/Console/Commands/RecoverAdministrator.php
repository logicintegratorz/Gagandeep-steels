<?php
namespace App\Console\Commands;

use App\Models\{Role, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RecoverAdministrator extends Command
{
    protected $signature = 'crm:recover-admin';
    protected $description = 'List administrator sign-in emails and securely reset an administrator using local terminal access';

    public function handle(): int
    {
        $admins = User::with('role')->whereHas('role', fn ($q) => $q->where('is_admin', true))->orderBy('id')->get();
        if ($admins->isEmpty()) {
            $this->error('No Administrator account exists. Use crm:create-admin.');
            return self::FAILURE;
        }
        $this->table(['ID', 'Name', 'Login email', 'Status'], $admins->map(fn ($user) => [$user->id, $user->name, $user->email, $user->active ? 'Active' : 'Inactive'])->all());
        $id = $this->ask('Administrator ID to recover (blank to cancel)');
        $admin = $admins->firstWhere('id', $id);
        if (!$admin) { $this->error('No matching administrator selected. Nothing changed.'); return self::FAILURE; }
        $email = strtolower(trim($this->ask('Login email (press Enter to keep current)', $admin->email)));
        $password = $this->secret('New password (8+ characters, letters and numbers)');
        $confirmation = $this->secret('Confirm new password');
        $validator = Validator::make(['email' => $email, 'password' => $password, 'password_confirmation' => $confirmation], [
            'email' => ['required', new \App\Rules\LoginEmail, 'max:255', Rule::unique('users')->ignore($admin->id)],
            'password' => ['required', 'confirmed', 'max:255', Password::min(8)->letters()->numbers()],
        ]);
        if ($validator->fails()) { foreach ($validator->errors()->all() as $error) $this->error($error); return self::FAILURE; }
        if (!$this->confirm('Reset this administrator and activate their account? All existing sessions will end.', false)) return self::FAILURE;
        DB::transaction(function () use ($admin, $email, $password) {
            Role::where('is_admin', true)->lockForUpdate()->firstOrFail();
            $current = User::whereKey($admin->id)->lockForUpdate()->firstOrFail();
            if (!$current->role->is_admin || $current->version !== $admin->version) throw new \RuntimeException('Account changed during recovery. Run recovery again.');
            DB::table('password_reset_tokens')->whereIn('email', [$current->email, $email])->delete();
            $current->forceFill(['email' => $email, 'password' => $password, 'active' => true, 'remember_token' => Str::random(60), 'session_version' => $current->session_version + 1, 'version' => $current->version + 1])->save();
        });
        $this->info('Administrator recovered. Sign in using '.$email.' and the new password.');
        $this->line('Passwords are never displayed or saved in plaintext by this command.');
        return self::SUCCESS;
    }
}
