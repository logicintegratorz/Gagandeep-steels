$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$php = Join-Path $PSScriptRoot '.local/php/php.exe'
if (!(Test-Path $php) -or !(Test-Path 'vendor/autoload.php') -or !(Test-Path '.env')) {
    throw 'Local setup is incomplete. See LOCAL-TESTING.md.'
}
$connection = New-Object System.Net.Sockets.TcpClient
try { $connection.Connect('127.0.0.1', 3307); $databaseRunning = $true } catch { $databaseRunning = $false } finally { $connection.Dispose() }
if (!$databaseRunning) {
    $mysql = Join-Path $PSScriptRoot '.local\mysql-8.4.9-winx64\bin\mysqld.exe'
    $mysqlConfig = Join-Path $PSScriptRoot '.local\my.ini'
    if (!(Test-Path $mysql) -or !(Test-Path $mysqlConfig)) {
        throw 'Local MySQL setup is missing. See LOCAL-TESTING.md.'
    }

    Write-Host 'Starting local MySQL on 127.0.0.1:3307...' -ForegroundColor Cyan
    Start-Process -FilePath $mysql -ArgumentList "--defaults-file=`"$mysqlConfig`"" -WorkingDirectory $PSScriptRoot -WindowStyle Hidden

    $databaseRunning = $false
    for ($attempt = 0; $attempt -lt 20; $attempt++) {
        Start-Sleep -Seconds 1
        $connection = New-Object System.Net.Sockets.TcpClient
        try {
            $connection.Connect('127.0.0.1', 3307)
            $databaseRunning = $true
            break
        } catch {
            # MySQL can take a few seconds while it recovers its data files.
        } finally {
            $connection.Dispose()
        }
    }

    if (!$databaseRunning) {
        throw 'Local MySQL could not start on 127.0.0.1:3307. Check .local\mysql-error.log.'
    }
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
