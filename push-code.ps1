[CmdletBinding()]
param(
    [string]$Server = '187.127.159.53',
    [string]$User = 'root',
    [ValidateRange(1, 65535)][int]$Port = 22,
    [string]$IdentityFile,
    [switch]$PackageOnly
)

$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
$releaseId = (Get-Date -Format 'yyyyMMdd-HHmmss') + '-' + [guid]::NewGuid().ToString('N').Substring(0, 8)
$archive = Join-Path ([IO.Path]::GetTempPath()) "steelsmandi-$releaseId.tar.gz"
$remoteStage = "/var/www/steelsmandi-releases/$releaseId"

function Invoke-Checked {
    param([string]$Program, [string[]]$Arguments)
    & $Program @Arguments
    if ($LASTEXITCODE -ne 0) { throw "$Program failed with exit code $LASTEXITCODE." }
}

if ($Server -notmatch '^[a-zA-Z0-9.-]+$' -or $User -notmatch '^[a-zA-Z0-9_-]+$') {
    throw 'Use a plain hostname/IP and SSH username.'
}
foreach ($program in @('tar', 'ssh', 'scp')) { Get-Command $program -ErrorAction Stop | Out-Null }
$sshArgs = @('-p', "$Port", '-o', 'ConnectTimeout=20')
$scpArgs = @('-P', "$Port", '-o', 'ConnectTimeout=20')
if ($IdentityFile) {
    $IdentityFile = (Resolve-Path -LiteralPath $IdentityFile).Path
    $sshArgs += @('-i', $IdentityFile)
    $scpArgs += @('-i', $IdentityFile)
}
$target = "${User}@${Server}"

try {
    Write-Host 'Packaging application source (no local secrets, uploads or database)...'
    # An allowlist prevents accidentally uploading .env, .local, Git or runtime data.
    $sourcePaths = @('app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'deploy',
        'artisan', 'composer.json', 'composer.lock', '.dockerignore')
    Invoke-Checked tar (@('-czf', $archive, '-C', $projectRoot,
        '--exclude=bootstrap/cache/*.php', '--exclude=public/storage', '--exclude=public/hot') + $sourcePaths)
    if ($PackageOnly) {
        Write-Host "Archive ready: $archive"
        return
    }
    Write-Host "Uploading to $target (CRM port 8082 only)..."
    Invoke-Checked ssh ($sshArgs + @($target, "umask 077; mkdir -p '$remoteStage'"))
    Invoke-Checked scp ($scpArgs + @($archive, "${target}:${remoteStage}/source.tar.gz"))
    $command = "set -eu; cd '$remoteStage'; tar -xzf source.tar.gz; sed -i 's/\r`$//' deploy/update.sh; bash deploy/update.sh '$remoteStage'"
    Invoke-Checked ssh ($sshArgs + @($target, $command))
    Write-Host "Deployment complete: http://${Server}:8082"
}
finally {
    if (!$PackageOnly -and (Test-Path -LiteralPath $archive)) { Remove-Item -LiteralPath $archive }
}
