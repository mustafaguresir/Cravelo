@echo off
title Cravelo - WAMP Proje Baglama ve Baslatma Sihirbazi
color 0A
echo ===========================================================================
echo   CRAVELO - WAMP SERVER OTOMATIK BAGLAMA VE BASLATMA
echo ===========================================================================
echo.

REM 1. C:\wamp64 kontrolu
if not exist "C:\wamp64" (
    echo [BILGI] C:\wamp64 klasoru henuz bulunamadi.
    echo.
    pause
    exit /b
)

REM 2. C:\wamp64\www kontrolu
if not exist "C:\wamp64\www" (
    echo [HATA] C:\wamp64\www klasoru bulunamadi!
    pause
    exit /b
)

REM 3. www altinda sembolik baglanti / junction olustur
echo [1/3] Proje WAMP www klasorune baglaniyor...
if exist "C:\wamp64\www\yemek-kurye-sistemi" (
    echo [OK] C:\wamp64\www\yemek-kurye-sistemi baglantisi zaten mevcut.
) else (
    mklink /J "C:\wamp64\www\yemek-kurye-sistemi" "%~dp0" >nul 2>&1
    if exist "C:\wamp64\www\yemek-kurye-sistemi" (
        echo [OK] Proje klasoru C:\wamp64\www\yemek-kurye-sistemi olarak baglandi!
    ) else (
        echo [BILGI] Baglanti komutu tamamlandi, kontrol ediliyor...
    )
)

REM 4. WampServer Yöneticisini baslat
echo.
echo [2/3] WampServer baslatiliyor...
if exist "C:\wamp64\wampmanager.exe" (
    start "" "C:\wamp64\wampmanager.exe"
    echo [OK] WampServer baslatildi. Sistem tepsisindeki (saat yani) W simgesinin yesil olmasini bekleyiniz.
)

REM 5. Tarayiciyi ac
echo.
echo [3/3] Tarayici adresi aciliyor...
timeout /t 3 >nul
start "" "http://localhost/yemek-kurye-sistemi/"

echo.
echo ===========================================================================
echo   ISLEM TAMAMLANDI!
echo   Tarayicinizda: http://localhost/yemek-kurye-sistemi/
echo   Giris Ekrani:  http://localhost/yemek-kurye-sistemi/login
echo.
echo   NOT: XAMPP ve WAMP ayni anda calistirilamaz (ikisi de Port 80 kullanir).
echo        WAMP acikken XAMPP'i, XAMPP acikken WAMP'i kapali tutunuz.
echo ===========================================================================
echo.
pause
