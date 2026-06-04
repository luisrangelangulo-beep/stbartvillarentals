param(
    [Parameter(Mandatory = $false)]
    [string]$RepoPath = "."
)

$ErrorActionPreference = "Stop"
Set-Location $RepoPath

Write-Host "Running preflight checks in $(Get-Location)" -ForegroundColor Cyan

$requiredFiles = @(
    ".github/workflows/deploy-theme.yml",
    ".github/workflows/ssh-probe.yml",
    ".github/workflows/quality.yml",
    "functions.php",
    "style.css"
)

$missing = @()
foreach ($f in $requiredFiles) {
    if (-not (Test-Path $f)) {
        $missing += $f
    }
}

if ($missing.Count -gt 0) {
    Write-Host "Missing required files:" -ForegroundColor Red
    $missing | ForEach-Object { Write-Host " - $_" }
    exit 1
}

$deploy = Get-Content ".github/workflows/deploy-theme.yml" -Raw
$probe  = Get-Content ".github/workflows/ssh-probe.yml" -Raw

if ($deploy -match "CHANGE_ME_CPANEL_USER" -or $probe -match "CHANGE_ME_CPANEL_USER") {
    Write-Host "CPANEL_USER placeholder still present. Update workflow env values before deploy." -ForegroundColor Yellow
    exit 2
}

$requiredSecrets = @(
    "SFTP_HOST",
    "SFTP_USER",
    "SFTP_PORT",
    "SFTP_PRIVATE_KEY"
)

Write-Host "Required repository secrets:" -ForegroundColor Cyan
$requiredSecrets | ForEach-Object { Write-Host " - $_" }
Write-Host "Optional secret: SFTP_PASSPHRASE" -ForegroundColor DarkCyan

Write-Host "Preflight passed. Ready to run SSH Probe and Deploy Theme." -ForegroundColor Green
exit 0
