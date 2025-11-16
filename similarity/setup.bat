@echo off
chcp 65001 >nul
echo.
echo ========================================
echo   Python Environment Setup
echo ========================================
echo.

REM Check Python
python --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Python not found!
    echo.
    echo Install Python 3.8+ from https://www.python.org/downloads/
    echo ✅ Check "Add Python to PATH" during installation
    pause
    exit /b 1
)
echo ✅ Python found:
python --version
echo.

REM Check if .venv exists
if exist ".venv" (
    echo ⚠️  Virtual environment already exists
    choice /C YN /M "Recreate environment? (Y - yes, N - no)"
    if errorlevel 2 goto :skip_venv
    echo 🗑️  Removing old environment...
    rmdir /s /q .venv
)

REM Create virtual environment
echo 📦 Creating virtual environment...
python -m venv .venv
if errorlevel 1 (
    echo ❌ Failed to create environment
    pause
    exit /b 1
)

:skip_venv

REM Activate environment
echo 🔌 Activating environment...
call .venv\Scripts\activate.bat

REM Update pip
echo 📥 Updating pip...
python -m pip install --upgrade pip --quiet

REM Install dependencies
echo 📚 Installing dependencies...
if not exist "requirements.txt" (
    echo ❌ requirements.txt not found!
    pause
    exit /b 1
)

pip install -r requirements.txt
if errorlevel 1 (
    echo ❌ Failed to install dependencies
    pause
    exit /b 1
)

echo.
echo ========================================
echo   🤖 Downloading model
echo ========================================
echo.

REM Run model download script
if exist "download_model.py" (
    python download_model.py
    if errorlevel 1 (
        echo ⚠️  Model download failed, but continuing...
    )
) else (
    echo ⚠️  download_model.py not found, skipping model download
)

echo.
echo ========================================
echo   ✅ Setup completed!
echo ========================================
echo.
echo 📝 To activate environment:
echo    .venv\Scripts\activate
echo.
echo 🚀 To start server:
echo    python -m uvicorn app:app --host 127.0.0.1 --port 8000
echo.
echo 💡 Or use PM2:
echo    pm2 start ecosystem.config.cjs
echo.
echo 📁 Model cache location:
echo    C:\Users\User\.cache\huggingface\hub
echo.
pause