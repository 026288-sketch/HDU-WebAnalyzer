<?php

namespace App\Services\Logs;

use App\Models\Log;

/**
 * Class LoggerService
 *
 * Service for creating log entries in the database.
 * Provides a simple interface to log messages with level, service, and context.
 */
class LoggerService
{
    /**
     * Create a new log entry in the `logs` table.
     *
     * @param  string  $service  Name of the service generating the log
     * @param  string  $level  Log level (e.g., info, error, warning)
     * @param  string  $message  Log message
     * @param  array  $context  Optional additional context data
     */
    public function log(string $service, string $level, string $message, array $context = []): void
    {
        Log::create([
            'service' => $service,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
