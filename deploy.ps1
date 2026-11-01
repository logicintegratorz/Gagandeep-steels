<#
    Safe Laravel/PostgreSQL deployment helper.

    Run on the production server from the project root:
      powershell -ExecutionPolicy Bypass -File .\deploy.ps1

    This script never runs `migrate:fresh` and never overwrites .env.
#>
[CmdletBinding()]
param(
    [switch]$SkipComposer,
    [switch]$SkipBackup,
    [switch]$AllowLocal,
    [string]$PhpPath = "",
    [string]$ComposerPath = "",
    [string]$PgDumpPath = ""
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectRoot

function Resolve-CommandPath([string]$preferred, [string]$fallback) {
    if ($preferred) {
        if (-not (Test-Path -LiteralPath $preferred)) { throw "Command was not found: $preferred" }
        return (Resolve-Path -LiteralPath $preferred).Path
    }

    $fromPath = Get-Command $fallback -ErrorAction SilentlyContinue
    if ($fromPath) { return $fromPath.Source }
    return $null
}

if (-not (Test-Path -LiteralPath '.env')) {
    throw 'Missing .env. Copy .env.production.example to .env and configure PostgreSQL before deploying.'
}

$php = Resolve-CommandPath $PhpPath 'php'
if (-not $php -and (Test-Path -LiteralPath '.local\php\php.exe')) { $php = (Resolve-Path '.local\php\php.exe').Path }
if (-not $php) { throw 'PHP was not found. Install PHP with pdo_pgsql enabled, or use -PhpPath.' }

$envLines = Get-Content '.env' | Where-Object { $_ -match '^(APP_ENV|DB_CONNECTION)=' }
$appEnvironment = ($envLines | Where-Object { $_ -match '^APP_ENV=' } | Select-Object -First 1) -replace '^APP_ENV=', ''
$databaseDriver = ($envLines | Where-Object { $_ -match '^DB_CONNECTION=' } | Select-Object -First 1) -replace '^DB_CONNECTION=', ''
if ($appEnvironment -ne 'production' -and -not ($AllowLocal -and $appEnvironment -eq 'local')) {
    throw "Refusing deployment: APP_ENV must be production (current: $appEnvironment). Use -AllowLocal only for this local development machine."
}
if ($databaseDriver -ne 'pgsql') { throw "Refusing deployment: DB_CONNECTION must be pgsql (current: $databaseDriver)." }

function Get-DotEnvValue([string]$name) {
    $line = Get-Content '.env' | Where-Object { $_ -match ("^{0}=" -f [regex]::Escape($name)) } | Select-Object -First 1
    if (-not $line) { return '' }
    return (($line -replace ("^{0}=" -f [regex]::Escape($name)), '').Trim().Trim('"').Trim("'"))
}

# PostgreSQL client utilities use PG* environment variables, while Laravel uses DB_*.
$env:PGHOST = Get-DotEnvValue 'DB_HOST'
$env:PGPORT = Get-DotEnvValue 'DB_PORT'
$env:PGDATABASE = Get-DotEnvValue 'DB_DATABASE'
$env:PGUSER = Get-DotEnvValue 'DB_USERNAME'
$env:PGPASSWORD = Get-DotEnvValue 'DB_PASSWORD'
if (-not $env:PGHOST -or -not $env:PGDATABASE -or -not $env:PGUSER) { throw 'Incomplete PostgreSQL DB_* configuration in .env.' }

if (-not $SkipBackup) {
    $pgDump = Resolve-CommandPath $PgDumpPath 'pg_dump'
    if (-not $pgDump) {
        Write-Warning 'pg_dump was not found; database backup was skipped. Install PostgreSQL client tools or rerun with -PgDumpPath.'
    } else {
        $backupDirectory = Join-Path $projectRoot 'storage\app\backups'
        New-Item -ItemType Directory -Force -Path $backupDirectory | Out-Null
        $backupFile = Join-Path $backupDirectory ("postgresql-before-deploy-{0}.dump" -f (Get-Date -Format 'yyyyMMdd-HHmmss'))
        & $pgDump --format=custom --file=$backupFile
        if ($LASTEXITCODE -ne 0) { throw 'PostgreSQL backup failed. Deployment stopped before migrations.' }
        Write-Host "PostgreSQL backup created: $backupFile" -ForegroundColor Green
    }
}

if (-not $SkipComposer) {
    $composer = Resolve-CommandPath $ComposerPath 'composer'
    if ($composer) {
        & $composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
    } elseif (Test-Path -LiteralPath '.local\composer.phar') {
        & $php '.local\composer.phar' install --no-dev --prefer-dist --no-interaction --optimize-autoloader
    } else {
        throw 'Composer was not found. Install Composer or use -ComposerPath.'
    }
    if ($LASTEXITCODE -ne 0) { throw 'Composer install failed.' }
}

& $php artisan down --render='errors::503' --retry=60
try {
    & $php artisan migrate --force
    if ($LASTEXITCODE -ne 0) { throw 'Migration failed.' }

    & $php artisan storage:link
    & $php artisan optimize:clear
    & $php artisan config:cache
    & $php artisan route:cache
    & $php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'Laravel cache build failed.' }
} finally {
    & $php artisan up
}

$target = if ($appEnvironment -eq 'production') { 'production' } else { 'local' }
Write-Host "Deployment completed successfully with PostgreSQL ($target)." -ForegroundColor Green
