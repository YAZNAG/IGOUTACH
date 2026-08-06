# Génère les deux APK IGOUTECH liés au serveur de production.
#
#   .\build-apk.ps1                      → serveur https://igoutech.optizaworks.com
#   .\build-apk.ps1 -ApiUrl "http://192.168.1.10:8001/api/v1"   → serveur local
#
# Les APK sont copiés dans .\apk\ :
#   IGOUTECH-Admin.apk        (profil direction, navigation complète)
#   IGOUTECH-Responsable.apk  (profil responsable d'un lieu)
#
# Le profil ne donne aucun droit : les permissions viennent toujours du serveur.

param(
    [string]$ApiUrl = "https://igoutech.optizaworks.com/api/v1"
)

$ErrorActionPreference = "Stop"
$flutter = "C:\flutter\bin\flutter.bat"
$root = $PSScriptRoot
$out = Join-Path $root "apk"

Set-Location $root
New-Item -ItemType Directory -Force -Path $out | Out-Null

Write-Host "== Analyse ==" -ForegroundColor Cyan
& $flutter analyze
if ($LASTEXITCODE -ne 0) { throw "flutter analyze a échoué — build annulé." }

foreach ($profile in @(
    @{ Name = "admin";   File = "IGOUTECH-Admin.apk" },
    @{ Name = "manager"; File = "IGOUTECH-Responsable.apk" }
)) {
    Write-Host "== APK profil $($profile.Name) → $ApiUrl ==" -ForegroundColor Cyan
    & $flutter build apk --release `
        --dart-define=API_URL=$ApiUrl `
        --dart-define=APP_PROFILE=$($profile.Name)
    if ($LASTEXITCODE -ne 0) { throw "Build $($profile.Name) échoué." }

    Copy-Item "build\app\outputs\flutter-apk\app-release.apk" `
              (Join-Path $out $profile.File) -Force
}

Write-Host ""
Write-Host "APK générés dans $out :" -ForegroundColor Green
Get-ChildItem $out -Filter *.apk |
    ForEach-Object { "  {0}  ({1:N1} Mo)" -f $_.Name, ($_.Length / 1MB) }
