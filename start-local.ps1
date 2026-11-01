$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$php = Join-Path $PSScriptRoot '.local/php/php.exe'
$mysql = Join-Path $PSScriptRoot '.local/mysql-8.4.9-winx64/bin/mysqld.exe'
if (!(Test-Path $php) -or !(Test-Path $mysql) -or !(Test-Path 'vendor/autoload.php') -or !(Test-Path '.env')) {
    throw 'Local setup is incomplete. See LOCAL-TESTING.md.'
}
$connection = New-Object System.Net.Sockets.TcpClient
try { $connection.Connect('127.0.0.1', 3307); $databaseRunning = $true } catch { $databaseRunning = $false } finally { $connection.Dispose() }
if (!$databaseRunning) {
    $defaults = Join-Path $PSScriptRoot '.local/my.ini'
    Start-Process -FilePath $mysql -ArgumentList ('--defaults-file="' + $defaults + '"') -WindowStyle Hidden | Out-Null
    $ready = $false
    for ($attempt = 0; $attempt -lt 30; $attempt++) {
        Start-Sleep -Seconds 1
        $connection = New-Object System.Net.Sockets.TcpClient
        try { $connection.Connect('127.0.0.1', 3307); $ready = $true; break } catch {} finally { $connection.Dispose() }
    }
    if (!$ready) { throw 'MySQL did not start. Check .local/mysql-error.log.' }
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
