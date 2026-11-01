<?php
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('crm:create-admin {--name=} {--email=}', function () {
    $name = $this->option('name') ?: $this->ask('Administrator name');
    $email = strtolower(trim($this->option('email') ?: $this->ask('Administrator email')));
    $password = $this->secret('Password (at least 8 characters, letters and numbers)');
    $confirmation = $this->secret('Confirm password');
    $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation], [
        'name' => ['required','string','max:100'], 'email' => ['required',new \App\Rules\LoginEmail,'max:255','unique:users,email'],
        'password' => ['required','confirmed','max:255',Password::min(8)->letters()->numbers()],
    ]);
    if ($validator->fails()) { foreach ($validator->errors()->all() as $error) $this->error($error); return 1; }
    User::create(['name' => $name, 'email' => $email, 'password' => $password, 'role_id' => Role::where('is_admin', true)->firstOrFail()->id, 'active' => true]);
    $this->info('Administrator created. Sign in at '.config('app.url').'/login');
    return 0;
})->purpose('Create an administrator locally with a hidden password prompt');

Artisan::command('crm:lead-reminders', function () { $this->info(\App\Support\LeadReminders::deliver().' overdue alerts delivered to responsible users and administrators.'); });
\Illuminate\Support\Facades\Schedule::command('crm:lead-reminders')->everyMinute()->withoutOverlapping();

Artisan::command('crm:stagnant-leads', function () { $this->info(\App\Support\StagnantLeadAlerts::deliver().' same-stage lead alerts delivered to responsible users and administrators.'); });
\Illuminate\Support\Facades\Schedule::command('crm:stagnant-leads')->hourly()->withoutOverlapping();

Artisan::command('crm:sync-leads', function () { foreach(\App\Models\LeadConnection::where('enabled',true)->where('provider','!=','Justdial')->get() as $connection) \App\Support\LeadSync::run($connection);$this->info('Lead connections checked.'); });
\Illuminate\Support\Facades\Schedule::command('crm:sync-leads')->everyFifteenMinutes()->withoutOverlapping();

Artisan::command('crm:list-automations', function () { $this->info(\App\Support\LeadListAutomations::run().' list automation actions processed.'); });
\Illuminate\Support\Facades\Schedule::command('crm:list-automations')->everyMinute()->withoutOverlapping();

Artisan::command('crm:list-queue', function () { $this->info(\App\Support\LeadListQueue::run().' queued messages attempted.'); });
\Illuminate\Support\Facades\Schedule::command('crm:list-queue')->everyMinute()->withoutOverlapping();

Artisan::command('crm:lead-tenure', function () { $result=\App\Support\LeadTenure::run();$this->info($result['created'].' lead backups updated; '.$result['purged'].' expired backups deleted.'); });
\Illuminate\Support\Facades\Schedule::command('crm:lead-tenure')->dailyAt('01:30')->withoutOverlapping();
