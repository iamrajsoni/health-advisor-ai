@echo off
echo.
echo ============================================
echo    Health Advisor AI - Server Starting
echo ============================================
echo.

:: Get local IP address
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /C:"IPv4"') do (
    set IP=%%a
    goto :found
)
:found
set IP=%IP: =%

echo  Server running at:
echo.
echo    Local:   http://localhost:8000
echo    Network: http://%IP%:8000
echo.
echo  Open the Network URL on your mobile (same WiFi)
echo  Press Ctrl+C to stop the server
echo ============================================
echo.

php -S 0.0.0.0:8000
