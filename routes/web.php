<?php

use App\Http\Controllers\ArticleParserController;
use App\Http\Controllers\ChromaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\LinkParserController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedArticleController;
use App\Http\Controllers\SentimentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TestParserController;
use Illuminate\Support\Facades\Route;

// Главная
Route::get('/', fn () => redirect('/dashboard'));

// Dashboard (только для авторизованных и подтверждённых)
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartDataAjax']);

    // 👤 Профиль пользователя
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // 📰 Источники
    Route::prefix('sources')->name('sources.')->group(function () {
        Route::get('/', [SourceController::class, 'index'])->name('index');
        Route::get('/create', [SourceController::class, 'create'])->name('create');
        Route::post('/', [SourceController::class, 'store'])->name('store');
        Route::delete('/{source}', [SourceController::class, 'destroy'])->name('destroy');
    });

    // 🧠 Парсер (ссылки, статьи, логи, тест)
    Route::prefix('parser')->name('parser.')->group(function () {
        // Парсер ссылок
        Route::get('/links', [LinkParserController::class, 'index'])->name('links');
        Route::post('/links/run', [LinkParserController::class, 'run'])->name('links.run');

        // Парсер контента
        Route::post('/parse-contents', [ArticleParserController::class, 'parse'])->name('contents');

        // Тестовая страница парсера
        Route::get('/test-parser', [TestParserController::class, 'index'])->name('test');
        Route::post('/test-links', [TestParserController::class, 'testLinks'])->name('test.links');
        Route::post('/test-content', [TestParserController::class, 'testContent'])->name('test.content');

        // Логи
        Route::get('/logs', [LogController::class, 'index'])->name('logs');
        Route::delete('/logs', [LogController::class, 'clear'])->name('logs.clear');
    });

    // 📚 Сохранённые статьи
    Route::prefix('content')->name('articles.')->group(function () {
        Route::get('/', [SavedArticleController::class, 'index'])->name('index');
        Route::post('/clear', [SavedArticleController::class, 'clear'])->name('clear');
        Route::get('/{id}', [SavedArticleController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [SavedArticleController::class, 'edit'])->name('edit');
        Route::patch('/{id}', [SavedArticleController::class, 'update'])->name('update');
        Route::delete('/{id}', [SavedArticleController::class, 'destroy'])->name('destroy');
    });

    // 🏷️ Теги и тональность
    Route::get('/tags/{slug}', [TagController::class, 'show'])->name('tags.show');
    Route::get('/sentiments/{type}/{value}', [SentimentController::class, 'show'])->name('sentiments.show');

    // 🆕 Экспорт
    Route::post('/export/nodes', [ExportController::class, 'exportNodes'])->name('export.nodes');
    Route::post('/export/stats', [ExportController::class, 'exportStats'])->name('export.stats');

    // Chroma db
    Route::get('/chroma', [ChromaController::class, 'index'])->name('chroma.index');
    Route::post('/chroma/check', [ChromaController::class, 'check'])->name('chroma.check');
    Route::post('/chroma/delete', [ChromaController::class, 'delete'])->name('chroma.delete');

    // 🟢 Сервисы
    Route::get('/services/status', [ServiceController::class, 'status'])->name('services.status');
    Route::post('/services/start', [ServiceController::class, 'startAll'])->name('services.start');
    Route::post('/services/stop', [ServiceController::class, 'stopAll'])->name('services.stopAll');

    Route::fallback(function () {
        abort(404);
    });
});

// Breeze (регистрация, логин, logout и т.д.)
require __DIR__.'/auth.php';
