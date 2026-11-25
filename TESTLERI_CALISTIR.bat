@echo off
setlocal enabledelayedexpansion
title Cravelo Asama 2 - Canli Test Ekrani (360 Test & PHPStan)
color 0A
set "FAILED=0"

echo ===========================================================================
echo   CRAVELO ASAMA 2: PHPUNIT, PHPSTAN, MIKRO-MIMARI, GUVENLIK VE ARAYUZ TESTLERI
echo ===========================================================================
echo.

echo [1/10] Python M1 Mimari Akislar, Base Path, PRG ve Flock Testleri (31 Test)...
python tests\test_architectural_flows.py
if errorlevel 1 set "FAILED=1"
echo.
echo ---------------------------------------------------------------------------
echo.

echo [2/10] Python CMD Cekirdek Sartname Testleri (20 Test)...
python tests\run_all_cmd_tests.py
if errorlevel 1 set "FAILED=1"
echo.
echo ---------------------------------------------------------------------------
echo.

echo [3/10] Python Sistem ve Depolama Dogrulama Testleri (8 Test)...
python tests\verify_system.py
if errorlevel 1 set "FAILED=1"
echo.
echo ---------------------------------------------------------------------------
echo.

echo [4/10] Node.js Adres Senkronu, Silme, 10 Kullanici ve KDS (31 Test)...
node tests\test_address_and_restaurant.js
if errorlevel 1 set "FAILED=1"
echo.
echo ---------------------------------------------------------------------------
echo.

echo [5/10] Node.js Rol Izolasyonu, Akilli Menu ve Harita HUD (42 Test)...
node tests\test_comprehensive_improvements.js
if errorlevel 1 set "FAILED=1"
echo.
echo ---------------------------------------------------------------------------
echo.

echo [6/10] Node.js Sikilastirilmis Giris ve Kayit Dogrulama (24 Test)...
node tests\test_login_and_registration.js
if errorlevel 1 set "FAILED=1"
echo.
echo ---------------------------------------------------------------------------
echo.

echo [7/10] Node.js Karsilama Ekrani, Auth Gate ve Rol Senkronizasyonu (35 Test)...
node tests\test_landing_and_auth_gate.js
if errorlevel 1 set "FAILED=1"
echo.
echo ---------------------------------------------------------------------------
echo.

echo [8/10] Node.js Burger Hero Scrollytelling, GSAP ve Cift Gorunum (58 Test)...
node tests\test_burger_hero_scrollytelling.js
if errorlevel 1 set "FAILED=1"
echo.
echo ---------------------------------------------------------------------------
echo.

echo [9/10] Node.js Derin Denetim Regresyon Testleri (10 Test)...
node tests\test_deep_audit_fixes.js
if errorlevel 1 set "FAILED=1"
echo.
echo ---------------------------------------------------------------------------
echo.

echo [10/10] Node.js Platform Ozellikleri, Moduler UI & SQL Hizalama Testleri (15 Test)...
node tests\test_platform_features.js
if errorlevel 1 set "FAILED=1"
echo.
echo ---------------------------------------------------------------------------
echo.

echo [*] PHPUnit (54 Test / 247 Assertion), PHPStan (Seviye 5) ve Cekirdek PHP Testleri (32 Test)...
set "PHP_BIN="
if exist "%~dp0php\php.exe" (
    set "PHP_BIN=%~dp0php\php.exe"
) else if exist "C:\xampp\php\php.exe" (
    set "PHP_BIN=C:\xampp\php\php.exe"
) else (
    for /f "delims=" %%I in ('where php 2^>nul') do if not defined PHP_BIN set "PHP_BIN=%%I"
)

if defined PHP_BIN (
    echo [OK] PHP calistirici bulundu: "!PHP_BIN!"
    if exist "%~dp0vendor\bin\phpunit" (
        echo --- [PHPUnit Birim Testleri] ---
        if exist "%~dp0php\ext" (
            "!PHP_BIN!" -d extension_dir="%~dp0php\ext" -d extension=mbstring "%~dp0vendor\bin\phpunit"
        ) else (
            "!PHP_BIN!" "%~dp0vendor\bin\phpunit"
        )
        if errorlevel 1 set "FAILED=1"
    )
    if exist "%~dp0vendor\bin\phpstan" (
        echo --- [PHPStan Statik Analiz - Level 5] ---
        if exist "%~dp0php\ext" (
            "!PHP_BIN!" -d extension_dir="%~dp0php\ext" -d extension=mbstring "%~dp0vendor\bin\phpstan" analyse --no-progress
        ) else (
            "!PHP_BIN!" "%~dp0vendor\bin\phpstan" analyse --no-progress
        )
        if errorlevel 1 set "FAILED=1"
    )
    "!PHP_BIN!" tests\run_tests.php
    if errorlevel 1 set "FAILED=1"
    "!PHP_BIN!" tests\test_architectural_flows.php
    if errorlevel 1 set "FAILED=1"
) else (
    echo [HATA] PHP 8.2+ calistirici bulunamadi!
    set "FAILED=1"
)

echo.
echo ===========================================================================
if "!FAILED!"=="0" (
    echo   TUM 360 TEST VE PHPSTAN SEVIYE 5 %100 BASARILI!
) else (
    color 0C
    echo   [HATA] BAZI TESTLER BASARISIZ OLDU! Yukaridaki ciktiyi inceleyiniz.
)
echo ===========================================================================
if "%CI%"=="" pause > nul
exit /b !FAILED!
