<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class ServiceController
 *
 * Controller to manage external services (Puppeteer and Python),
 * including health checks, start, and stop operations.
 */
class ServiceController extends Controller
{
    // === Health Checks ===

    /**
     * Get the status of the Puppeteer service.
     */
    public static function getPuppeteerStatus(): array
    {
        try {
            $start = microtime(true);
            $response = Http::timeout(3)->get('http://127.0.0.1:3000/health');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful() && $response->json('status') === 'ok') {
                return [
                    'status' => 'online',
                    'message' => $response->json('message', 'Running'),
                    'response_time' => $responseTime,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Service returned an error',
                'response_time' => null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'message' => 'Service is unavailable',
                'response_time' => null,
            ];
        }
    }

    /**
     * Get the status of the Python service.
     */
    public static function getPythonStatus(): array
    {
        try {
            $start = microtime(true);
            $response = Http::timeout(3)->get('http://127.0.0.1:8000/health');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful() && $response->json('status') === 'ok') {
                return [
                    'status' => 'online',
                    'message' => $response->json('message', 'Running'),
                    'response_time' => $responseTime,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Service returned an error',
                'response_time' => null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'message' => 'Service is unavailable',
                'response_time' => null,
            ];
        }
    }

    /**
     * Get statuses of all services.
     */
    public static function getAllStatuses(): array
    {
        return [
            'puppeteer' => self::getPuppeteerStatus(),
            'python' => self::getPythonStatus(),
        ];
    }

    /**
     * Return JSON response with all service statuses.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        return response()->json(self::getAllStatuses());
    }

    // === Service Control ===

    /**
     * Start all services (Puppeteer and Python).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function startAll(Request $request)
    {
        try {
            Log::info('Starting all services', [
                'user' => $request->user()?->email ?? 'unknown',
            ]);

            $workDirPuppeteer = base_path('app/Services/Scraper');
            $workDirPython = base_path('similarity');
            $logFilePuppeteer = storage_path('logs/puppeteer.log');
            $logFilePython = storage_path('logs/python.log');

            if (PHP_OS_FAMILY === 'Windows') {
                // Start Puppeteer
                $cmdPuppeteer = "start /B node \"{$workDirPuppeteer}\\server.js\" > \"{$logFilePuppeteer}\" 2>&1";
                pclose(popen("cmd /c \"{$cmdPuppeteer}\"", 'r'));

                // Start Python via batch
                $batFile = $workDirPython.'\\start_python.bat';
                $cmdPython = "start /B cmd /c \"{$batFile}\" > \"{$logFilePython}\" 2>&1";
                pclose(popen("cmd /c \"{$cmdPython}\"", 'r'));
            } else {
                // Linux / Mac
                $pythonPath = $workDirPython.'/.venv/bin/python';
                exec("cd {$workDirPuppeteer} && nohup node server.js > {$logFilePuppeteer} 2>&1 &");
                exec("cd {$workDirPython} && nohup {$pythonPath} -m uvicorn app:app --host 127.0.0.1 --port 8000 > {$logFilePython} 2>&1 &");
            }

            sleep(3);

            $puppeteerStatus = self::getPuppeteerStatus();
            $pythonStatus = self::getPythonStatus();

            $messages = [];
            $messages[] = $puppeteerStatus['status'] === 'online'
                ? '✅ Puppeteer'
                : '⏳ Puppeteer is starting';

            $messages[] = $pythonStatus['status'] === 'online'
                ? '✅ Python'
                : '⏳ Python is starting';

            return response()->json([
                'success' => true,
                'message' => implode(' | ', $messages),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start services', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stop a specific service by its name (puppeteer or python).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopService(Request $request, string $service)
    {
        try {
            $port = match ($service) {
                'puppeteer' => 3000,
                'python' => 8000,
                default => null
            };

            if (! $port) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unknown service',
                ], 400);
            }

            Log::info("Stopping service: {$service} on port {$port}", [
                'user' => $request->user()?->email ?? 'unknown',
            ]);

            if (PHP_OS_FAMILY === 'Windows') {
                // Find processes on the port
                $output = [];
                exec("netstat -ano | findstr \":{$port}\"", $output);

                $killed = 0;
                foreach ($output as $line) {
                    if (preg_match('/\s+(\d+)\s*$/', $line, $matches)) {
                        $pid = $matches[1];
                        exec("taskkill /F /PID {$pid} 2>&1", $killOutput);
                        $killed++;
                    }
                }

                if ($killed === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "No processes found on port {$port}",
                    ]);
                }
            } else {
                // Linux / Mac
                $pids = [];
                exec("lsof -t -i:{$port} 2>/dev/null", $pids);

                if (empty($pids)) {
                    return response()->json([
                        'success' => false,
                        'message' => "No processes found on port {$port}",
                    ]);
                }

                foreach ($pids as $pid) {
                    exec("kill -9 {$pid} 2>/dev/null");
                }
            }

            sleep(1);

            $status = $service === 'puppeteer'
                ? self::getPuppeteerStatus()
                : self::getPythonStatus();

            return response()->json([
                'success' => $status['status'] === 'offline',
                'message' => $status['status'] === 'offline'
                    ? "✅ Service {$service} stopped"
                    : "⚠️ Service {$service} is still running (may require more time)",
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to stop service: {$service}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stop all services (Puppeteer and Python).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopAll(Request $request)
    {
        try {
            Log::info('Stopping all services', [
                'user' => $request->user()?->email ?? 'unknown',
            ]);

            $results = [];

            if (PHP_OS_FAMILY === 'Windows') {
                // Stop Puppeteer
                $output = [];
                exec('netstat -ano | findstr ":3000"', $output);
                foreach ($output as $line) {
                    if (preg_match('/\s+(\d+)\s*$/', $line, $matches)) {
                        $pid = $matches[1];
                        exec("taskkill /F /PID {$pid} 2>&1");
                        $results['puppeteer_pid'] = $pid;
                    }
                }

                // Stop Python
                $output = [];
                exec('netstat -ano | findstr ":8000"', $output);
                foreach ($output as $line) {
                    if (preg_match('/\s+(\d+)\s*$/', $line, $matches)) {
                        $pid = $matches[1];
                        exec("taskkill /F /PID {$pid} 2>&1");
                        $results['python_pid'] = $pid;
                    }
                }
            } else {
                // Linux / Mac
                exec('lsof -t -i:3000 2>/dev/null', $pids);
                if (! empty($pids)) {
                    foreach ($pids as $pid) {
                        exec("kill -9 {$pid} 2>/dev/null");
                    }
                    $results['puppeteer_killed'] = count($pids);
                }

                exec('lsof -t -i:8000 2>/dev/null', $pids);
                if (! empty($pids)) {
                    foreach ($pids as $pid) {
                        exec("kill -9 {$pid} 2>/dev/null");
                    }
                    $results['python_killed'] = count($pids);
                }
            }

            sleep(2);

            $puppeteerStatus = self::getPuppeteerStatus();
            $pythonStatus = self::getPythonStatus();

            $messages = [];
            $messages[] = $puppeteerStatus['status'] === 'offline'
                ? '✅ Puppeteer stopped'
                : '⚠️ Puppeteer still running';

            $messages[] = $pythonStatus['status'] === 'offline'
                ? '✅ Python stopped'
                : '⚠️ Python still running';

            return response()->json([
                'success' => true,
                'message' => implode(' | ', $messages),
                'details' => $results,
                'puppeteer_status' => $puppeteerStatus['status'],
                'python_status' => $pythonStatus['status'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to stop all services', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error stopping services: '.$e->getMessage(),
            ], 500);
        }
    }
}
