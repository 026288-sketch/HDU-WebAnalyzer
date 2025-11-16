const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');
require('dotenv').config({ path: path.join(__dirname, '..', '.env') });

// Используем путь к Python из .env или fallback по умолчанию
const pythonPath = process.env.PYTHON_PATH || (
    process.platform === 'win32'
        ? path.join(__dirname, '.venv', 'Scripts', 'python.exe')
        : path.join(__dirname, '.venv', 'bin', 'python')
);

const workDir = __dirname;

// Проверка существования Python
if (!fs.existsSync(pythonPath)) {
    console.error(`Python not found at: ${pythonPath}`);
    console.error('Please create virtual environment: python -m venv .venv');
    process.exit(1);
}

console.log(`Starting Python service with: ${pythonPath} in ${workDir}`);
console.log(`Host: ${process.env.PYTHON_HOST}, Port: ${process.env.PYTHON_PORT}, Log level: ${process.env.PYTHON_LOG_LEVEL}`);

// Запуск Python/uvicorn
const subprocess = spawn(
    pythonPath,
    [
        '-m', 'uvicorn',
        'app:app',
        '--host', process.env.PYTHON_HOST || '127.0.0.1',
        '--port', process.env.PYTHON_PORT || '8000',
        '--log-level', 'info'
    ],
    {
        cwd: workDir,
        stdio: 'inherit',
        shell: true
    }
);

subprocess.on('error', (error) => {
    console.error(`Failed to start Python process: ${error.message}`);
    process.exit(1);
});

subprocess.on('close', (code) => {
    console.log(`Python process exited with code ${code}`);
    if (code !== 0) {
        process.exit(code);
    }
});

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('Shutting down Python service...');
    subprocess.kill('SIGINT');
    setTimeout(() => process.exit(0), 1000);
});
