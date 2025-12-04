<?php

namespace App\Providers;

use App\Models\Log as AppLog;
use App\Models\Node;
use App\Models\NodeLink;
use App\Models\NodeSentiment;
use App\Models\Source;
use App\Models\Tag;
use App\Observers\LogObserver;
use App\Observers\NodeLinkObserver;
use App\Observers\NodeObserver;
use App\Observers\NodeSentimentObserver;
use App\Observers\SourceObserver;
use App\Observers\TagObserver;
use app\Services\Dashboard\DashboardCounterService;
use App\Services\logs\LoggerService;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoggerService::class, function ($app) {
            return new LoggerService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Применение Tailwind CSS для пагинации
        Paginator::useTailwind();
        Node::observe(NodeObserver::class);
        NodeLink::observe(NodeLinkObserver::class);
        NodeSentiment::observe(NodeSentimentObserver::class);
        Tag::observe(TagObserver::class);
        Source::observe(SourceObserver::class);
        AppLog::observe(LogObserver::class);

        // Учет успешных запусков консольных команд
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if ((int) $event->exitCode === 0 && ! empty($event->command)) {
                // Считаем только прямые запуски Console* команд
                // DataWorkflowCommand учитывается отдельно внутри себя
                if (str_starts_with($event->command, 'Console') && $event->command !== 'DataWorkflowCommand') {
                    $counters = app(DashboardCounterService::class);
                    $counters->incrementConsoleSuccess($event->command);
                    $counters->updateLastConsoleRun($event->command);
                }
            }
        });
    }
}
