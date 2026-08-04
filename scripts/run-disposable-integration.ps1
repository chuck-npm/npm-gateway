$ErrorActionPreference = 'Stop'

function Invoke-Gateway([string[]] $Arguments) {
    & php bin/gateway @Arguments
    if ($LASTEXITCODE -ne 0) { throw "gateway command failed: $($Arguments -join ' ')" }
}

function Assert-Profile([string] $Profile) {
    $output = & php bin/gateway database:check $Profile 2>&1
    $code = $LASTEXITCODE
    $output | ForEach-Object { Write-Host $_ }
    if ($code -ne 0 -or ($output -join "`n") -notmatch '(?m)^Selected database: npmgateway_test$') {
        throw "$Profile profile did not resolve exactly to npmgateway_test; no mutation was performed."
    }
}

$saved = @{}
foreach ($name in @('DB_NAME','MIGRATION_DB_NAME','WASABI_COMPANY_NOTICE_ATTACHMENTS_PREFIX','WASABI_COMPANY_NOTICE_IMAGES_PREFIX','WASABI_TEST_PREFIX','RUN_DB_INTEGRATION_TESTS','RUN_COMMIT015_E2E','APP_URL')) {
    $saved[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}

try {
    Write-Host '===== NORMAL DATABASE BASELINE ====='
    $normalBefore = (& php tests/Integration/database_fingerprint.php 2>&1) -join "`n"
    if ($LASTEXITCODE -ne 0 -or $normalBefore -notmatch '^npmgateway [a-f0-9]{64}$') { throw 'Normal database baseline fingerprint failed.' }
    Write-Host $normalBefore

    $env:DB_NAME = 'npmgateway_test'
    $env:MIGRATION_DB_NAME = 'npmgateway_test'
    $env:WASABI_COMPANY_NOTICE_ATTACHMENTS_PREFIX = 'company_notices/test/attachments/'
    $env:WASABI_COMPANY_NOTICE_IMAGES_PREFIX = 'company_notices/test/images/'
    $env:WASABI_TEST_PREFIX = 'company_notices/test/'
    $env:RUN_DB_INTEGRATION_TESTS = 'true'
    $env:RUN_COMMIT015_E2E = 'true'
    $env:APP_URL = 'https://gateway.example.test'

    Write-Host '===== MANDATORY PROFILE GATE ====='
    Assert-Profile 'application'
    Assert-Profile 'migration'

    Write-Host '===== MIGRATIONS AND SCHEMA ====='
    Invoke-Gateway @('migrate:status')
    & php tests/Integration/commit015_schema_restore.php
    if ($LASTEXITCODE -ne 0) { throw 'Disposable storage schema restore failed.' }
    Invoke-Gateway @('schema:verify')

    Write-Host '===== STORAGE PREFLIGHT AND ISOLATED PROVIDER DIAGNOSTIC ====='
    Invoke-Gateway @('upload:check')
    Invoke-Gateway @('storage:check')
    Invoke-Gateway @('storage:test')

    Write-Host '===== APPLICATION AND DISPOSABLE INTEGRATION TESTS ====='
    & vendor/bin/phpunit.bat
    if ($LASTEXITCODE -ne 0) { throw 'First full PHPUnit pass failed.' }
    & vendor/bin/phpunit.bat
    if ($LASTEXITCODE -ne 0) { throw 'Second full PHPUnit pass failed.' }

    Write-Host '===== DISPOSABLE DATABASE CLEANUP AND RESIDUE CHECK ====='
    & php tests/Integration/commit015_final_state.php
    if ($LASTEXITCODE -ne 0) { throw 'Disposable database cleanup/residue check failed.' }
    & php tests/Integration/commit015_schema_restore.php
    if ($LASTEXITCODE -ne 0) { throw 'Post-test disposable schema restore failed.' }
    Invoke-Gateway @('schema:verify')

    Write-Host '===== FINAL ISOLATED PROVIDER DELETE CONFIRMATION ====='
    Invoke-Gateway @('storage:test')
}
finally {
    foreach ($entry in $saved.GetEnumerator()) { [Environment]::SetEnvironmentVariable($entry.Key, $entry.Value, 'Process') }
}

Write-Host '===== NORMAL DATABASE FINAL ====='
$normalAfter = (& php tests/Integration/database_fingerprint.php 2>&1) -join "`n"
if ($LASTEXITCODE -ne 0 -or $normalAfter -notmatch '^npmgateway [a-f0-9]{64}$') { throw 'Normal database final fingerprint failed.' }
Write-Host $normalAfter
if ($normalBefore -ne $normalAfter) { throw 'Normal npmgateway database fingerprint changed.' }
Write-Host 'Normal npmgateway fingerprint unchanged: yes'
