@echo off
title SuperMart 3D POS & Inventory Server
color 0b
echo ==============================================================
echo        SUPERMART 3D BILLING & INVENTORY SYSTEM
echo         (PHP + Three.js - 100%% Offline & Online)
echo ==============================================================
echo.

set PHP_EXE=php
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_EXE="C:\xampp\php\php.exe"
        echo [INFO] Found PHP at C:\xampp\php\php.exe
    ) else (
        echo [ERROR] PHP is not found in PATH or C:\xampp\php\php.exe!
        echo Please ensure XAMPP is installed at C:\xampp or PHP is added to PATH.
        pause
        exit /b 1
    )
)

echo [INFO] Starting POS Web Server at http://localhost:8080 ...
echo [INFO] Opening browser...
start http://localhost:8080

echo.
echo ==============================================================
echo  Server is RUNNING! Press Ctrl+C in this window to stop.
echo ==============================================================
%PHP_EXE% -S localhost:8080
pause

