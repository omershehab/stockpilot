param(
    [string] $HostAddress = '127.0.0.1',
    [ValidateRange(1, 65535)]
    [int] $Port = 8000
)

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$bundledPhp = Join-Path $projectRoot '.runtime\php\php.exe'

if (Test-Path -LiteralPath $bundledPhp) {
    $phpExecutable = $bundledPhp
} else {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if (-not $phpCommand) {
        throw 'PHP 8.2 or later is required. Install PHP or provide .runtime\php\php.exe.'
    }

    $phpExecutable = $phpCommand.Source
}

Set-Location -LiteralPath $projectRoot
& $phpExecutable artisan serve --host=$HostAddress --port=$Port
exit $LASTEXITCODE
