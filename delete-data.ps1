[CmdletBinding()]
param(
    [switch]$Execute,
    [string]$Server = '187.127.159.53',
    [ValidateRange(1,65535)][int]$Port = 22,
    [string]$IdentityFile
)
$ErrorActionPreference = 'Stop'
if ($Server -notmatch '^[a-zA-Z0-9.-]+$') { throw 'Invalid SSH server.' }
if (!$Execute) {
    Write-Host 'PREVIEW ONLY: resets all CRM database records/users and uploaded files on port 8082.'
    Write-Host 'A backup is taken first. A new administrator password is generated on the server.'
    Write-Host 'To run: .\delete-data.ps1 -Execute (then type the confirmation when prompted).'
    return
}
$sshOptions = @('-t', '-p', "$Port")
if ($IdentityFile) { $sshOptions += @('-i', (Resolve-Path -LiteralPath $IdentityFile).Path) }
& ssh @sshOptions "root@$Server" "bash /var/www/steelsmandi/deploy/reset-data.sh --execute"
if ($LASTEXITCODE -ne 0) { throw 'CRM data reset did not complete successfully.' }
