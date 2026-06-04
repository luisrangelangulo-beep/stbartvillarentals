param(
    [Parameter(Mandatory = $false)]
    [string]$RepoPath = "."
)

$ErrorActionPreference = "Stop"
Set-Location $RepoPath

if (-not (Get-Command gh -ErrorAction SilentlyContinue)) {
    Write-Host "GitHub CLI (gh) is not installed or not in PATH." -ForegroundColor Red
    Write-Host "Install: https://cli.github.com/" -ForegroundColor Yellow
    exit 1
}

Write-Host "Triggering workflows in order..." -ForegroundColor Cyan

Write-Host "1) Quality" -ForegroundColor DarkCyan
gh workflow run "Quality (PHP lint)" | Out-Host

Write-Host "2) SSH Probe (temp - delete after)" -ForegroundColor DarkCyan
gh workflow run "SSH Probe (temp - delete after)" | Out-Host

Write-Host "3) Deploy Theme" -ForegroundColor DarkCyan
gh workflow run "Deploy Theme" | Out-Host

Write-Host "Done. Check GitHub Actions tab for run logs." -ForegroundColor Green
exit 0
