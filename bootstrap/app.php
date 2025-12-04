<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // 1️⃣ Workflow: every 10 minutes
        $schedule->command('data:workflow')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/scheduler-workflow-'.date('Y-m').'.log'))
            ->before(function () {
                Log::info('🚀 Starting scheduled workflow', [
                    'scheduled_at' => now()->toDateTimeString(),
                ]);
            })
            ->onSuccess(function () {
                Log::info('✅ Scheduled workflow completed successfully', [
                    'completed_at' => now()->toDateTimeString(),
                ]);
            })
            ->onFailure(function () {
                Log::error('❌ Scheduled workflow failed', [
                    'failed_at' => now()->toDateTimeString(),
                ]);
            });

        // 2️⃣ Stats sync: every day at 00:05
        $schedule->command('stats:sync')
            ->dailyAt('00:05')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/stats-sync.log'))
            ->before(function () {
                Log::info('📊 Starting statistics sync', [
                    'scheduled_at' => now()->toDateTimeString(),
                ]);
            })
            ->onSuccess(function () {
                Log::info('✅ Statistics sync completed successfully', [
                    'completed_at' => now()->toDateTimeString(),
                ]);
            })
            ->onFailure(function () {
                Log::error('❌ Statistics sync failed', [
                    'failed_at' => now()->toDateTimeString(),
                ]);
            });
    })
    ->create();
