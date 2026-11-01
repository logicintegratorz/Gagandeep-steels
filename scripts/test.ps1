$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot
$env:APP_ENV = 'testing'

# Windows PHP can incorrectly report the Composer bootstrap as unreadable to
# PHPUnit. Preloading it avoids that false bootstrap failure.
$testTargets = @($args)
if ($testTargets.Count -eq 0) {
    $testTargets = @('tests\Feature')
}

& .\.local\php\php.exe `
    -d auto_prepend_file=vendor\autoload.php `
    vendor\phpunit\phpunit\phpunit `
    --no-configuration `
    @testTargets

exit $LASTEXITCODE
