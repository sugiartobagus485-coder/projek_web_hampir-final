@echo off
REM ============================================
REM  PINTASAN BUKA WEBSITE GAMEDEV LEWAT XAMPP
REM  Klik 2x file ini. Jangan klik file .html/.php
rem  langsung, karena PHP hanya jalan lewat XAMPP.
REM ============================================

start http://localhost/ProjekHtmlRevisiiii/home.html

echo.
echo  Kalau halaman tidak muncul:
echo  1. Buka XAMPP Control Panel
echo  2. Klik "Start" di Apache (dan MySQL)
echo  3. Klik 2x file ini lagi
echo.
timeout /t 8 >nul
