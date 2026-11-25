@echo off
title Cravelo - XAMPP Proje Baglama ve Baslatma Sihirbazi
color 0B
echo ===========================================================================
echo   CRAVELO - XAMPP OTOMATIK BAGLAMA VE BASLATMA
echo ===========================================================================
echo.

REM 1. C:\xampp kontrolu
if not exist "C:\xampp" (
    echo [BILGI] C:\xampp klasoru henuz bulunamadi.
    echo Lutfen once proje klasorundeki "XAMPP_KUR.exe" dosyasina cift tiklayarak
    echo kurulumu tamamlayiniz.
    echo.
    pause
    exit /b
)

REM 2. C:\xampp\htdocs kontrolu
if not exist "C:\xampp\htdocs" (
    echo [HATA] C:\xampp\htdocs klasoru bulunamadi!
    pause
    exit /b
)

REM 3. htdocs altinda sembolik baglanti / junction olustur
echo [1/3] Proje XAMPP htdocs altina baglaniyor...
if exist "C:\xampp\htdocs\yemek-kurye-sistemi" (
    echo [OK] C:\xampp\htdocs\yemek-kurye-sistemi baglantisi zaten mevcut.
) else (
    mklink /J "C:\xampp\htdocs\yemek-kurye-sistemi" "%~dp0" >nul 2>&1
    if exist "C:\xampp\htdocs\yemek-kurye-sistemi" (
        echo [OK] Proje klasoru C:\xampp\htdocs\yemek-kurye-sistemi olarak baglandi!
    ) else (
        echo [BILGI] Baglanti komutu tamamlandi, kontrol ediliyor...
    )
)

REM 4. XAMPP Control Panel'i ac
echo.
echo [2/3] XAMPP Kontrol Paneli baslatiliyor...
if exist "C:\xampp\xampp-control.exe" (
    start "" "C:\xampp\xampp-control.exe"
    echo [OK] Kontrol Paneli acildi. Lutfen "Apache" satirindaki "Start" butonuna basiniz.
)

REM 5. Tarayiciyi ac
echo.
echo [3/3] Tarayici adresi aciliyor...
timeout /t 2 >nul
start "" "http://localhost/yemek-kurye-sistemi/"

echo.
echo ===========================================================================
echo   ISLEM TAMAMLANDI!
echo   Tarayicinizda: http://localhost/yemek-kurye-sistemi/
echo   Giris Ekrani:  http://localhost/yemek-kurye-sistemi/login
echo ===========================================================================
echo.
pause
