$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$php = Join-Path $PSScriptRoot '.local/php/php.exe'
if (!(Test-Path $php) -or !(Test-Path 'vendor/autoload.php') -or !(Test-Path '.env')) {
    throw 'Local setup is incomplete. See LOCAL-TESTING.md.'
}
$connection = New-Object System.Net.Sockets.TcpClient
try { $connection.Connect('127.0.0.1', 5432); $databaseRunning = $true } catch { $databaseRunning = $false } finally { $connection.Dispose() }
if (!$databaseRunning) {
    throw 'PostgreSQL is not running on 127.0.0.1:5432. Start the postgresql-x64-18 Windows service and try again.'
}
& $php artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Database migration failed.' }
Write-Host 'Gagandeep Steels CRM: http://127.0.0.1:8000'
Write-Host 'Keep this window open. Press Ctrl+C to stop the web server.'
$leadScheduler = Start-Process -FilePath $php -ArgumentList 'artisan schedule:work' -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -PassThru
try {
    & $php artisan serve --host=127.0.0.1 --port=8000
} finally {
    if (!$leadScheduler.HasExited) { Stop-Process -Id $leadScheduler.Id }
}
