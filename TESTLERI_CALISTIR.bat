@echo off
title Cravelo Asama 2 - Canli Test Ekrani (312 Test & PHPStan)
color 0A
echo ===========================================================================
echo   CRAVELO ASAMA 2: PHPUNIT, PHPSTAN, MIKRO-MIMARI, GUVENLIK VE ARAYUZ TESTLERI
echo ===========================================================================
echo.
echo [1/8] Python M1 Mimari Akislar, Base Path, PRG ve Flock Testleri (31 Test)...
python tests\test_architectural_flows.py
echo.
echo ---------------------------------------------------------------------------
echo.
echo [2/8] Python CMD Cekirdek Sartname Testleri (20 Test)...
python tests\run_all_cmd_tests.py
echo.
echo ---------------------------------------------------------------------------
echo.
echo [3/8] Python Sistem ve Depolama Dogrulama Testleri (8 Test)...
python tests\verify_system.py
echo.
echo ---------------------------------------------------------------------------
echo.
echo [4/8] Node.js Adres Senkronu, Silme, 10 Kullanici ve KDS (33 Test)...
node tests\test_address_and_restaurant.js
echo.
echo ---------------------------------------------------------------------------
echo.
echo [5/8] Node.js Rol Izolasyonu, Akilli Menu ve Harita HUD (46 Test)...
node tests\test_comprehensive_improvements.js
echo.
echo ---------------------------------------------------------------------------
echo.
echo [6/8] Node.js Sikilastirilmis Giris ve Kayit Dogrulama (24 Test)...
node tests\test_login_and_registration.js
echo.
echo ---------------------------------------------------------------------------
echo.
echo [7/8] Node.js Karsilama Ekrani, Auth Gate ve Rol Senkronizasyonu (35 Test)...
node tests\test_landing_and_auth_gate.js
echo.
echo ---------------------------------------------------------------------------
echo.
echo [8/8] Node.js Burger Hero Scrollytelling, GSAP ve Cift Gorunum (58 Test)...
node tests\test_burger_hero_scrollytelling.js
echo.
echo ---------------------------------------------------------------------------
echo.
echo [*] PHPUnit (vendor/bin/phpunit), PHPStan (Seviye 5) ve Cekirdek PHP Testleri...
if exist "%~dp0php\php.exe" (
    echo [OK] Gomulu PHP 8.2 ile calistiriliyor...
    if exist "%~dp0vendor\bin\phpunit" (
        echo --- [PHPUnit Birim Testleri] ---
        "%~dp0php\php.exe" -d extension_dir="%~dp0php\ext" -d extension=mbstring "%~dp0vendor\bin\phpunit"
    )
    if exist "%~dp0vendor\bin\phpstan" (
        echo --- [PHPStan Statik Analiz - Level 5] ---
        "%~dp0php\php.exe" -d extension_dir="%~dp0php\ext" -d extension=mbstring "%~dp0vendor\bin\phpstan" analyse --no-progress
    )
    "%~dp0php\php.exe" tests\run_tests.php
    "%~dp0php\php.exe" tests\test_architectural_flows.php
)
if exist "C:\xampp\php\php.exe" (
    echo [OK] XAMPP PHP 8.2 ile dogrulaniyor...
    "C:\xampp\php\php.exe" tests\run_tests.php
)
if exist "C:\wamp64\bin\php\php8.2.36\php.exe" (
    echo [OK] WAMP PHP 8.2 ile dogrulaniyor...
    "C:\wamp64\bin\php\php8.2.36\php.exe" tests\run_tests.php
)
echo.
echo ===========================================================================
echo   TUM TESTLER TAMAMLANDI. Kapatmak icin bir tusa basin...
echo ===========================================================================
pause > nul
