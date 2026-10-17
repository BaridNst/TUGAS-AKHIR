@echo off
set PORT=%1
if "%PORT%"=="" set PORT=8000
echo ======================================================
echo Menjalankan Cloudflare Tunnel untuk Port %PORT%...
echo HTTPS Publik tanpa halaman peringatan/interstitial!
echo ======================================================
if not exist "%~dp0cloudflared.exe" (
    echo Mengunduh cloudflared.exe...
    curl -L "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -o "%~dp0cloudflared.exe"
)
"%~dp0cloudflared.exe" tunnel --url http://localhost:%PORT%

