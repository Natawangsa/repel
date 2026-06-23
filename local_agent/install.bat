@echo off
echo ============================================
echo  HaFI85 Printer Agent - Installer
echo ============================================
echo.

REM Check Python
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python belum terinstall!
    echo Download di: https://www.python.org/downloads/
    echo Pastikan centang "Add Python to PATH" saat install.
    pause
    exit /b
)

echo [OK] Python ditemukan
echo.

REM Install dependencies
echo Installing dependencies...
pip install requests
echo.

echo [OPTIONAL] Untuk SNMP monitoring (ink level):
echo   pip install pysnmp
echo.

echo ============================================
echo  PENTING: Edit config.json dulu!
echo  Ganti api_url dengan URL hosting web kamu
echo ============================================
echo.
echo Contoh:
echo   "api_url": "https://hafi85.domain.com/api/printer-status"
echo.
echo Setelah config.json diedit, jalankan:
echo   python hafi85_agent.py
echo.
pause
