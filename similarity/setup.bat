@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion

echo.
echo ========================================
echo   Python Environment Setup
echo ========================================
echo.

REM Check Python
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python not found!
    echo.
    echo Install Python 3.8+ from https://www.python.org/downloads/
    echo [INFO] Check "Add Python to PATH" during installation
    pause
    exit /b 1
)
echo [OK] Python found:
python --version
echo.

REM Check if .venv exists
if exist ".venv" (
    echo [WARNING] Virtual environment already exists
    choice /C YN /M "Recreate environment? (Y - yes, N - no)"
    if errorlevel 2 goto :skip_venv
    echo [INFO] Removing old environment...
    rmdir /s /q .venv
    if errorlevel 1 (
        echo [ERROR] Failed to remove old environment
        pause
        exit /b 1
    )
)

REM Create virtual environment
echo [INFO] Creating virtual environment...
python -m venv .venv
if errorlevel 1 (
    echo [ERROR] Failed to create environment
    pause
    exit /b 1
)

:skip_venv
REM Activate environment
echo [INFO] Activating environment...
call .venv\Scripts\activate.bat
if errorlevel 1 (
    echo [ERROR] Failed to activate environment
    pause
    exit /b 1
)

REM Update pip
echo [INFO] Updating pip...
python -m pip install --upgrade pip --quiet
if errorlevel 1 (
    echo [WARNING] Pip update failed, continuing anyway...
)

REM Install dependencies
echo [INFO] Installing dependencies...
if not exist "requirements.txt" (
    echo [ERROR] requirements.txt not found!
    pause
    exit /b 1
)
pip install -r requirements.txt
if errorlevel 1 (
    echo [ERROR] Failed to install dependencies
    pause
    exit /b 1
)

echo.
echo ========================================
echo   Model Download
echo ========================================
echo.

REM Run model download script
if exist "download_model.py" (
    echo [INFO] Running model download...
    python download_model.py
    if errorlevel 1 (
        echo [WARNING] Model download failed, but continuing...
    )
) else (
    echo [WARNING] download_model.py not found, skipping model download
)

echo.
echo ========================================
echo   Setup completed successfully!
echo ========================================
echo.
echo [INFO] To activate environment in future:
echo    .venv\Scripts\activate
echo.
echo [INFO] To start server:
echo    python -m uvicorn app:app --host 127.0.0.1 --port 8000
echo.
echo [INFO] Or use PM2:
echo    pm2 start ecosystem.config.cjs
echo.
echo [INFO] Model cache location:
echo    %USERPROFILE%\.cache\huggingface\hub
echo.
pause