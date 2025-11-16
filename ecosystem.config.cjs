const path = require('path');

module.exports = {
    apps: [
        {
            name: 'puppeteer',
            script: path.join(__dirname, 'app', 'Services', 'Scraper', 'server.js'),
            watch: false,
            instances: 1,
            autorestart: true,
            max_restarts: 10,
            min_uptime: '10s',
            env: {
                NODE_ENV: 'production'
            },
            error_file: path.join(__dirname, 'logs', 'puppeteer-error.log'),
            out_file: path.join(__dirname, 'logs', 'puppeteer-out.log'),
            log_date_format: 'YYYY-MM-DD HH:mm:ss'
        },
        {
            name: 'python-service',
            script: path.join(__dirname, 'similarity', 'run_python.cjs'),
            watch: false,
            instances: 1,
            autorestart: true,
            max_restarts: 10,
            min_uptime: '10s',
            interpreter: 'node',
            env: {
                NODE_ENV: 'production'
            },
            error_file: path.join(__dirname, 'logs', 'python-error.log'),
            out_file: path.join(__dirname, 'logs', 'python-out.log'),
            log_date_format: 'YYYY-MM-DD HH:mm:ss'
        }
    ]
};