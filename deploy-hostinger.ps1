<#
One-command deployment for the Gagandeep CRM on Hostinger shared hosting.

Usage from this repository in Windows PowerShell:
  powershell -ExecutionPolicy Bypass -File .\deploy-hostinger.ps1

The script uploads application source only. It never uploads .env, vendor,
uploads, logs or databases. The live .env and database remain on Hostinger.
#>
[CmdletBinding()]
param(
    [string]$Server = '217.21.91.166',
    [ValidateRange(1, 65535)][int]$Port = 65002,
    [string]$User = 'u372639622',
    [string]$Domain = 'lightpink-manatee-807105.hostingersite.com',
    [string]$IdentityFile
)

$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
$releaseId = Get-Date -Format 'yyyyMMdd-HHmmss'
$archive = Join-Path ([IO.Path]::GetTempPath()) "gagandeep-crm-$releaseId.tar.gz"
$appRoot = "/home/$User/domains/$Domain/crmapp"
$publicRoot = "/home/$User/domains/$Domain/public_html"
$remoteArchive = "/home/$User/domains/$Domain/.deploy-$releaseId.tar.gz"

function Invoke-Checked {
    param([string]$Program, [string[]]$Arguments)
    & $Program @Arguments
    if ($LASTEXITCODE -ne 0) { throw "$Program failed with exit code $LASTEXITCODE." }
}

foreach ($program in @('tar', 'ssh', 'scp')) { Get-Command $program -ErrorAction Stop | Out-Null }
if ($Server -notmatch '^[a-zA-Z0-9.-]+$' -or $User -notmatch '^[a-zA-Z0-9_-]+$' -or $Domain -notmatch '^[a-zA-Z0-9.-]+$') {
    throw 'Server, user and domain must contain only normal hostname characters.'
}

$sshArgs = @('-p', "$Port", '-o', 'ConnectTimeout=30')
$scpArgs = @('-P', "$Port", '-o', 'ConnectTimeout=30')
if ($IdentityFile) {
    $IdentityFile = (Resolve-Path -LiteralPath $IdentityFile).Path
    $sshArgs += @('-i', $IdentityFile)
    $scpArgs += @('-i', $IdentityFile)
}
$target = "${User}@${Server}"

try {
    Write-Host '1/4 Packaging source (local secrets and data are excluded)...' -ForegroundColor Cyan
    $sourcePaths = @('app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'deploy',
        'artisan', 'composer.json', 'composer.lock', '.gitattributes', '.gitignore')
    Invoke-Checked tar (@('-czf', $archive, '-C', $projectRoot,
        '--exclude=bootstrap/cache/*.php', '--exclude=public/storage', '--exclude=public/hot') + $sourcePaths)

    Write-Host '2/4 Uploading release to Hostinger...' -ForegroundColor Cyan
    Invoke-Checked scp ($scpArgs + @($archive, "${target}:$remoteArchive"))

    Write-Host '3/4 Applying release, migrations and optimized caches...' -ForegroundColor Cyan
    # Only a temporary incoming directory is replaced. .env is never part of the archive.
    # An absolute remote path is used because a quoted ~ is not expanded by Bash.
    $remoteCommand = "set -e; APP=`"$appRoot`"; PUBLIC=`"$publicRoot`"; ARCHIVE=`"$remoteArchive`"; INCOMING=`"`$APP/.deploy-incoming`"; rm -rf `"`$INCOMING`"; mkdir -p `"`$INCOMING`"; tar -xzf `"`$ARCHIVE`" -C `"`$INCOMING`"; cp -a `"`$INCOMING/.`" `"`$APP/`"; rm -rf `"`$INCOMING`" `"`$ARCHIVE`"; bash `"`$APP/deploy/hostinger-update.sh`" `"`$APP`" `"`$PUBLIC`""
    Invoke-Checked ssh ($sshArgs + @($target, $remoteCommand))
    Write-Host '4/4 Deployment complete.' -ForegroundColor Green
    Write-Host "Open: https://$Domain/login" -ForegroundColor Green
}
finally {
    if (Test-Path -LiteralPath $archive) { Remove-Item -LiteralPath $archive -Force }
}
