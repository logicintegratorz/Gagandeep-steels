#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
if [ "${1:-}" != --execute ]; then
    echo 'PREVIEW ONLY: resets the steelsmandi database (including all users) and CRM uploads.'
    echo 'A backup is required first. A fresh admin login is saved in /root/steelsmandi-first-login.txt.'
    echo 'Run with --execute and type the confirmation to proceed.'
    exit 0
fi
app=/var/www/steelsmandi
cd "$app"
compose=(docker compose -p steelsmandi --project-directory "$app" -f "$app/compose.yaml")
# Fail closed against any different runtime database, even if .env was changed.
"${compose[@]}" exec -T app php < /dev/stdin <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'steelsmandi' || config('database.connections.mysql.host') !== 'db') {
    fwrite(STDERR, "Unexpected database. Nothing deleted.\n"); exit(1);
}
PHP
echo 'This deletes ALL live CRM records, users and uploads on port 8082.'
read -r -p 'Type DELETE-STEELSMANDI-DATA to continue: ' answer
[ "$answer" = DELETE-STEELSMANDI-DATA ] || { echo 'Cancelled. Nothing deleted.'; exit 1; }
exec 9>/run/steelsmandi-deploy.lock
flock -n 9 || { echo 'CRM deployment/reset is already running.'; exit 1; }
exec 8>/run/steelsmandi-scheduler.lock
flock -w 120 8
"${compose[@]}" exec -T -u www-data app php artisan down --retry=30 < /dev/null
# On error keep maintenance mode enabled: a partial reset must not become public.
trap 'echo "Reset failed. CRM remains in maintenance mode. Use the backup to recover." >&2' ERR
bash "$app/deploy/backup.sh" < /dev/null
"${compose[@]}" exec -T -u www-data app php artisan migrate:fresh --force < /dev/null
"${compose[@]}" exec -T -u www-data app php > /root/steelsmandi-first-login.txt <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Only known Laravel runtime directories, on this app's dedicated storage volume.
$fs = new Illuminate\Filesystem\Filesystem;
foreach (['app', 'framework/sessions', 'framework/cache/data', 'framework/views'] as $directory) {
    $path = storage_path($directory);
    if (is_link($path)) throw new RuntimeException('Refusing a symlinked storage directory.');
    if (is_dir($path) && !$fs->cleanDirectory($path)) throw new RuntimeException('Storage cleanup failed.');
}
$password = bin2hex(random_bytes(14)).'Aa7!';
App\Models\User::create(['name'=>'Administrator','email'=>'admin@steelsmandi.com','password'=>$password,'role_id'=>App\Models\Role::where('is_admin',true)->firstOrFail()->id,'active'=>true]);
echo 'URL: '.config('app.url')."\nEmail: admin@steelsmandi.com\nPassword: ".$password."\nChange the password after first login.\n";
PHP
chmod 600 /root/steelsmandi-first-login.txt
"${compose[@]}" exec -T -u www-data app php artisan optimize < /dev/null
"${compose[@]}" exec -T -u www-data app php artisan up < /dev/null
curl --fail --silent --show-error http://127.0.0.1:8082/login > /dev/null
trap - ERR
echo 'CRM reset complete. Backups and environment settings are retained.'
echo 'Read the new login with: cat /root/steelsmandi-first-login.txt'
