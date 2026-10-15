@echo off
set PORT=%1
if "%PORT%"=="" set PORT=8000
echo ======================================================
echo Starting Ngrok Tunnel for Port %PORT%...
echo ======================================================
ngrok http %PORT%
