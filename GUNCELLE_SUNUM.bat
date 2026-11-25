@echo off
chcp 65001 >nul
title Cravelo Asama 2 — Sunum Olusturucu ve Esitleyici
echo ========================================================================
echo   Cravelo — Aşama 2 Sunum Üretici & Doğrulayıcı
echo ========================================================================
echo.
python build_full_presentation.py --verify
if errorlevel 1 (
    echo.
    echo [!] Sunum olusturulurken hata olustu! Python ve python-pptx kurulu mu?
    pause
    exit /b 1
)
echo.
echo ========================================================================
echo   [✓] Cravelo_Asama2_Sunum.pptx başarıyla güncellendi ve doğrulandı!
echo ========================================================================
echo.
pause
