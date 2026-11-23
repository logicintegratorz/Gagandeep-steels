<#
Compatibility entry point for the production Hostinger deployment.

Run from the VS Code terminal:
  powershell -ExecutionPolicy Bypass -File .\push-code.ps1

It delegates to deploy-hostinger.ps1, which uploads only application source to
the active Hostinger CRM. Local .env, database, logs and uploads are excluded.
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
$deploymentScript = Join-Path $PSScriptRoot 'deploy-hostinger.ps1'

if (-not (Test-Path -LiteralPath $deploymentScript -PathType Leaf)) {
    throw 'deploy-hostinger.ps1 is missing. Deployment cannot start.'
}

$parameters = @{
    Server = $Server
    Port = $Port
    User = $User
    Domain = $Domain
}
if ($IdentityFile) {
    $parameters.IdentityFile = $IdentityFile
}

& $deploymentScript @parameters
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
