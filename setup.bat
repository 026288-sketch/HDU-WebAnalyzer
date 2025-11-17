@echo off
setlocal enabledelayedexpansion

REM Set code page without output
chcp 65001 >nul 2>&1

REM ========================================
REM   Project Root Verification
REM ========================================

set PROJECT_ROOT=%cd%
set SIMILARITY_FOLDER=%PROJECT_ROOT%\similarity
set MODEL_CACHE=%USERPROFILE%\.cache\huggingface\hub

if not exist "composer.json" (
    echo [ERROR] composer.json not found!
    echo [ERROR] Please run this script from project root directory
    echo [ERROR] Expected: %PROJECT_ROOT%
    pause
    exit /b 1
)

echo.
echo ========================================
echo   HDU-WebAnalyzer Setup Script
echo ========================================
echo.

REM ========================================
REM   Warning About Installation Time
REM ========================================

echo [WARNING] This setup process may take 10-30 minutes
echo [WARNING] Dependencies are being downloaded and installed:
echo   - PHP packages (Composer)
echo   - Node.js packages (npm)
echo   - Python virtual environment
echo   - AI models (large files)
echo.
choice /C YN /M "Do you want to continue"
if errorlevel 2 (
    echo [INFO] Setup cancelled by user
    exit /b 0
)

echo.
echo ========================================
echo   Step 1: Installing PHP Dependencies
echo ========================================
echo.

REM Check if composer is available
where composer >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Composer not found in PATH!
    echo [INFO] Please ensure Composer is installed and added to PATH
    pause
    exit /b 1
)

echo [INFO] Composer found:
call composer --version
echo.

REM Check if vendor folder exists
if exist "vendor" (
    echo [INFO] Vendor folder already exists
    choice /C YN /M "Reinstall PHP dependencies"
    if errorlevel 2 (
        echo [INFO] Skipping Composer install
        goto :skip_composer
    )
    echo [INFO] Reinstalling dependencies...
)

echo [INFO] Running Composer install...
echo [INFO] This may take 5-10 minutes...
echo.

REM Run composer with proper timeout and output
call composer install --no-interaction --prefer-dist --no-progress

if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Composer install failed with code: %errorlevel%
    pause
    exit /b 1
)

echo.
echo [OK] PHP dependencies installed
echo.

:skip_composer

echo.
echo ========================================
echo   Step 2: Installing Node.js Dependencies
echo ========================================
echo.

if not exist "package.json" (
    echo [WARNING] package.json not found, skipping npm install
    goto :skip_npm
)

REM Check if npm is available
where npm >nul 2>&1
if %errorlevel% neq 0 (
    echo [WARNING] npm not found in PATH, skipping npm install
    goto :skip_npm
)

REM Check if node_modules exists
if exist "node_modules" (
    echo [INFO] node_modules folder already exists
    choice /C YN /M "Reinstall Node.js dependencies"
    if errorlevel 2 (
        echo [INFO] Skipping npm install
        goto :skip_npm
    )
    echo [INFO] Reinstalling dependencies...
)

echo [INFO] npm version:
call npm --version
echo.
echo [INFO] Running npm install...
call npm install

if %errorlevel% neq 0 (
    echo [WARNING] npm install failed, but continuing...
) else (
    echo [OK] Node.js dependencies installed
)

:skip_npm

echo.
echo ========================================
echo   Step 3: Python Environment Setup
echo ========================================
echo.

REM Check Python
where python >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Python not found in PATH!
    echo [ERROR] Install Python 3.8+ from https://www.python.org/downloads/
    echo [INFO] Check "Add Python to PATH" during installation
    pause
    exit /b 1
)

echo [OK] Python found:
python --version
echo.

REM Check similarity folder
if not exist "%SIMILARITY_FOLDER%" (
    echo [ERROR] Similarity folder not found: %SIMILARITY_FOLDER%
    pause
    exit /b 1
)

cd /d "%SIMILARITY_FOLDER%"
if %errorlevel% neq 0 (
    echo [ERROR] Failed to navigate to similarity folder
    cd /d "%PROJECT_ROOT%"
    pause
    exit /b 1
)

echo [INFO] Working in: %cd%
echo.

REM Check if .venv exists
if exist ".venv" (
    echo [INFO] Virtual environment already exists
    choice /C YN /M "Recreate virtual environment"
    if errorlevel 2 goto :skip_venv_create
    
    echo [INFO] Removing old environment...
    rmdir /s /q .venv 2>nul
    if exist ".venv" (
        echo [ERROR] Failed to remove old environment
        echo [INFO] Close any Python processes and try again
        cd /d "%PROJECT_ROOT%"
        pause
        exit /b 1
    )
)

echo [INFO] Creating Python virtual environment...
python -m venv .venv
if %errorlevel% neq 0 (
    echo [ERROR] Failed to create virtual environment
    cd /d "%PROJECT_ROOT%"
    pause
    exit /b 1
)

:skip_venv_create

REM Activate environment
echo [INFO] Activating Python virtual environment...
if not exist ".venv\Scripts\activate.bat" (
    echo [ERROR] Virtual environment activation script not found
    cd /d "%PROJECT_ROOT%"
    pause
    exit /b 1
)

call .venv\Scripts\activate.bat
if %errorlevel% neq 0 (
    echo [ERROR] Failed to activate virtual environment
    cd /d "%PROJECT_ROOT%"
    pause
    exit /b 1
)

echo [OK] Virtual environment activated
echo.

REM Update pip
echo [INFO] Updating pip...
python -m pip install --upgrade pip --quiet
if %errorlevel% neq 0 (
    echo [WARNING] Pip update failed, continuing anyway...
)

REM Install dependencies
echo [INFO] Installing Python dependencies...
if not exist "requirements.txt" (
    echo [ERROR] requirements.txt not found!
    cd /d "%PROJECT_ROOT%"
    pause
    exit /b 1
)

echo [INFO] This may take several minutes...
pip install -r requirements.txt

if %errorlevel% neq 0 (
    echo [ERROR] Failed to install Python dependencies
    cd /d "%PROJECT_ROOT%"
    pause
    exit /b 1
)

echo [OK] Python dependencies installed
echo.

echo.
echo ========================================
echo   Step 4: Downloading AI Model
echo ========================================
echo.

if exist "download_model.py" (
    echo [INFO] Found download_model.py
    
    REM Check if model files actually exist (not just the cache folder)
    set MODEL_EXISTS=0
    if exist "%MODEL_CACHE%\models--*" set MODEL_EXISTS=1
    
    if !MODEL_EXISTS! equ 1 (
        echo [INFO] AI model appears to be already downloaded
        choice /C YN /M "Re-download AI model"
        if errorlevel 2 goto skip_model
        echo [INFO] Re-downloading model...
    ) else (
        echo [INFO] AI model not found, downloading...
    )
    
    echo [INFO] This may take 5-15 minutes depending on your connection
    echo [INFO] Please be patient, downloading large files...
    python download_model.py
    
    if errorlevel 1 (
        echo [WARNING] Model download failed
        echo [INFO] You can download it later by running:
        echo [INFO]   cd similarity
        echo [INFO]   .venv\Scripts\activate
        echo [INFO]   python download_model.py
    ) else (
        echo [OK] AI model downloaded successfully
    )
) else (
    echo [WARNING] download_model.py not found, skipping model download
)

:skip_model

REM Return to project root
cd /d "%PROJECT_ROOT%"

echo.
echo ========================================
echo   Step 5: Environment Configuration
echo ========================================
echo.

if not exist ".env" (
    if not exist ".env.example" (
        echo [WARNING] .env.example not found
        echo [INFO] You may need to configure .env manually
    ) else (
        echo [INFO] Creating .env file...
        copy .env.example .env >nul
        if %errorlevel% neq 0 (
            echo [ERROR] Failed to copy .env.example
            pause
            exit /b 1
        )
        
        echo [INFO] Generating Laravel APP_KEY...
        php artisan key:generate
        if %errorlevel% neq 0 (
            echo [WARNING] Failed to generate APP_KEY
            echo [INFO] Run manually: php artisan key:generate
        ) else (
            echo [OK] APP_KEY generated
        )
    )
) else (
    echo [INFO] .env file already exists
    echo [INFO] To regenerate APP_KEY: php artisan key:generate
)

echo.
echo ========================================
echo   Optional: Database Setup
echo ========================================
echo.

choice /C YN /M "Run database migrations"
if errorlevel 1 (
    if errorlevel 2 goto :skip_migrations
    
    echo [INFO] Running migrations...
    php artisan migrate
    
    if %errorlevel% neq 0 (
        echo [WARNING] Migration failed
        echo [INFO] Configure database in .env and run: php artisan migrate
    ) else (
        echo [OK] Migrations completed
    )
)

:skip_migrations

echo.
echo ========================================
echo   Setup Completed Successfully!
echo ========================================
echo.
echo [INFO] Project structure:
echo   - Laravel app: %PROJECT_ROOT%
echo   - Python env: %SIMILARITY_FOLDER%\.venv
echo   - Model cache: %MODEL_CACHE%
echo.
echo ========================================
echo   Quick Start Commands
echo ========================================
echo.
echo [1] Start Laravel development server:
echo     php artisan serve
echo.
echo [2] Build frontend assets:
echo     npm run dev     (for development with hot reload)
echo     npm run build   (for production)
echo.
echo [3] Activate Python environment:
echo     cd similarity
echo     .venv\Scripts\activate
echo.
echo [4] Run Laravel queue worker (if needed):
echo     php artisan queue:work
echo.
echo [5] Clear Laravel cache (if issues):
echo     php artisan cache:clear
echo     php artisan config:clear
echo     php artisan view:clear
echo.
echo ========================================
echo.
echo [SUCCESS] You can now start developing!
echo.
echo Press any key to exit...
pause >nul