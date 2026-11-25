@echo off
title Cravelo Asama 2 - Web Sunucusu (PHP 8.2)
color 0B
echo ===========================================================================
echo   CRAVELO ASAMA 2: PROJE BASLATILIYOR (PHP 8.2)...
echo ===========================================================================
echo.

REM 1. Proje icindeki Gomulu PHP 8.2 kontrolu
if exist "%~dp0php\php.exe" (
    echo [OK] Proje icindeki PHP 8.2 motoru tespit edildi.
    echo [OK] Web sunucusu baslatiliyor: http://localhost:8000
    start "" "http://localhost:8000"
    "%~dp0php\php.exe" -S localhost:8000 -t public public/index.php
    goto :END
)

REM 2. XAMPP PHP kontrolu (C:\xampp\php\php.exe)
if exist "C:\xampp\php\php.exe" (
    echo [OK] XAMPP PHP 8.2 tespit edildi. Localhost sunucusu baslatiliyor...
    start "" "http://localhost:8000"
    "C:\xampp\php\php.exe" -S localhost:8000 -t public public/index.php
    goto :END
)

REM 3. WAMP PHP kontrolu (C:\wamp64\bin\php\php8.2.36\php.exe)
if exist "C:\wamp64\bin\php\php8.2.36\php.exe" (
    echo [OK] WAMP PHP 8.2 tespit edildi. Localhost sunucusu baslatiliyor...
    start "" "http://localhost:8000"
    "C:\wamp64\bin\php\php8.2.36\php.exe" -S localhost:8000 -t public public/index.php
    goto :END
)

REM 4. Sistemdeki genel PHP'yi kontrol et
where php >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] Sistem PHP tespit edildi. Localhost sunucusu baslatiliyor...
    start "" "http://localhost:8000"
    php -S localhost:8000 -t public public/index.php
    goto :END
)

REM 5. Python 'py' launcher kontrolu
where py >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] Python (py launcher) tespit edildi. Localhost baslatiliyor...
    start "" "http://localhost:8000"
    py server.py
    goto :END
)

REM 6. Local AppData icindeki Python'i tara
for /d %%D in ("%LOCALAPPDATA%\Programs\Python\Python3*") do (
    if exist "%%D\python.exe" (
        echo [OK] Python AppData icinde tespit edildi. Localhost baslatiliyor...
        start "" "http://localhost:8000"
        "%%D\python.exe" server.py
        goto :END
    )
)

REM 7. Sifir kurulumla tarayicida index.html ac
echo [UYARI] Bu bilgisayarda PHP veya Python tanimli degil.
echo [OK] Uygulama sifir kurulumla dogrudan tarayicida aciliyor...
echo.
start "" "%~dp0public\index.html"

:END
pause
