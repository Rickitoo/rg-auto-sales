param(
    [string]$PhpPath = "",
    [string]$ProjectRoot = ""
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
    $ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
} else {
    $ProjectRoot = Resolve-Path $ProjectRoot
}

function Resolve-PhpCli {
    param([string]$RequestedPath)

    if (-not [string]::IsNullOrWhiteSpace($RequestedPath)) {
        if (Test-Path $RequestedPath) {
            return (Resolve-Path $RequestedPath).Path
        }

        throw "PHP CLI informado nao existe: $RequestedPath"
    }

    $pathPhp = Get-Command php -ErrorAction SilentlyContinue
    if ($pathPhp) {
        return $pathPhp.Source
    }

    $knownPaths = @(
        "C:\xampp\php\php.exe",
        "C:\laragon\bin\php\php.exe",
        "C:\wamp64\bin\php\php.exe"
    )

    foreach ($candidate in $knownPaths) {
        if (Test-Path $candidate) {
            return $candidate
        }
    }

    return $null
}

$php = Resolve-PhpCli -RequestedPath $PhpPath

if (-not $php) {
    Write-Host "PHP CLI nao encontrado." -ForegroundColor Red
    Write-Host ""
    Write-Host "Como configurar no Windows/XAMPP:"
    Write-Host "1. Confirme que existe: C:\xampp\php\php.exe"
    Write-Host "2. Abra: Sistema > Configuracoes avancadas > Variaveis de Ambiente"
    Write-Host "3. Em Path, adicione: C:\xampp\php"
    Write-Host "4. Feche e abra o terminal novamente"
    Write-Host "5. Teste com: php -v"
    Write-Host ""
    Write-Host "Ou execute este script apontando direto para o PHP:"
    Write-Host "powershell -ExecutionPolicy Bypass -File scripts\lint-php.ps1 -PhpPath C:\xampp\php\php.exe"
    exit 2
}

$ignoredDirs = @(
    ".git",
    "vendor",
    "node_modules",
    "backups",
    "backup",
    "uploads",
    "storage\reports"
)

$phpFiles = Get-ChildItem -Path $ProjectRoot -Recurse -File -Filter "*.php" | Where-Object {
    $relative = $_.FullName.Substring($ProjectRoot.Path.Length).TrimStart("\", "/")
    foreach ($dir in $ignoredDirs) {
        if ($relative -eq $dir -or $relative.StartsWith($dir + "\", [System.StringComparison]::OrdinalIgnoreCase)) {
            return $false
        }
    }
    return $true
}

$ok = New-Object System.Collections.Generic.List[string]
$errors = New-Object System.Collections.Generic.List[object]

foreach ($file in $phpFiles) {
    $output = & $php -l $file.FullName 2>&1
    $exitCode = $LASTEXITCODE
    $relativePath = $file.FullName.Substring($ProjectRoot.Path.Length).TrimStart("\", "/")

    if ($exitCode -eq 0) {
        $ok.Add($relativePath)
        continue
    }

    $message = ($output | Out-String).Trim()
    $line = ""

    $lineMatch = [regex]::Match($message, " on line ([0-9]+)")
    if ($lineMatch.Success) {
        $line = $lineMatch.Groups[1].Value
    }

    $errors.Add([pscustomobject]@{
        File = $relativePath
        Line = $line
        Error = $message
    })
}

$reportDir = Join-Path $ProjectRoot "storage\reports"
if (-not (Test-Path $reportDir)) {
    New-Item -ItemType Directory -Path $reportDir | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$reportPath = Join-Path $reportDir "php-lint-$timestamp.txt"

$report = New-Object System.Collections.Generic.List[string]
$report.Add("RG_AUTO_SALES PHP Lint Report")
$report.Add("Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')")
$report.Add("Project: $($ProjectRoot.Path)")
$report.Add("PHP CLI: $php")
$report.Add("")
$report.Add("Summary")
$report.Add("OK: $($ok.Count)")
$report.Add("Errors: $($errors.Count)")
$report.Add("")
$report.Add("Files OK")
foreach ($item in $ok) {
    $report.Add("[OK] $item")
}
$report.Add("")
$report.Add("Syntax Errors")
if ($errors.Count -eq 0) {
    $report.Add("None")
} else {
    foreach ($err in $errors) {
        $lineInfo = if ([string]::IsNullOrWhiteSpace($err.Line)) { "" } else { " line $($err.Line)" }
        $report.Add("[ERROR] $($err.File)$lineInfo")
        $report.Add($err.Error)
        $report.Add("")
    }
}

Set-Content -Path $reportPath -Value $report -Encoding UTF8

Write-Host "PHP CLI: $php"
Write-Host "Arquivos OK: $($ok.Count)" -ForegroundColor Green
Write-Host "Arquivos com erro: $($errors.Count)" -ForegroundColor $(if ($errors.Count -eq 0) { "Green" } else { "Red" })
Write-Host "Relatorio: $reportPath"

if ($errors.Count -gt 0) {
    Write-Host ""
    Write-Host "Erros encontrados:" -ForegroundColor Red
    foreach ($err in $errors) {
        $lineInfo = if ([string]::IsNullOrWhiteSpace($err.Line)) { "" } else { " linha $($err.Line)" }
        Write-Host "- $($err.File)$lineInfo"
        Write-Host "  $($err.Error)"
    }
    exit 1
}

exit 0
