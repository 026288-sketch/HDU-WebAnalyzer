<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_counters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->enum('counter_type', ['total', 'delta'])->default('total');
            $table->json('value')->nullable();
            $table->timestamps();

            // Индексы
            $table->index('key', 'dashboard_counters_key_index');
            $table->index('counter_type', 'dashboard_counters_counter_type_index');
        });

        // 🔹 Первичное заполнение всех ключей
        DB::table('dashboard_counters')->insert([
            // 1️⃣ Общие счетчики
            ['key' => 'total_nodes', 'counter_type' => 'total', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'total_tags', 'counter_type' => 'total', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'total_sources', 'counter_type' => 'total', 'value' => json_encode(['count' => 0, 'rss' => 0, 'browser_required' => 0]), 'created_at' => now(), 'updated_at' => now()],

            // 2️⃣ Тональность
            ['key' => 'nodes_sentiment_positive', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nodes_sentiment_negative', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nodes_sentiment_neutral', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],

            // 3️⃣ Эмоции
            ['key' => 'nodes_emotion_anger', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nodes_emotion_sadness', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nodes_emotion_disgust', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nodes_emotion_fear', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nodes_emotion_joy', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nodes_emotion_surprise', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nodes_emotion_neutral', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],

            // 4️⃣ Парсинг
            ['key' => 'nodes_parsed', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nodes_duplicates', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nodes_missing_content', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],

            // 5️⃣ Ошибки
            [
                'key' => 'errors',
                'counter_type' => 'delta',
                'value' => json_encode([
                    'count' => 0,
                    'types' => [
                        'parser' => 0,
                        'ai_service' => 0,
                        'embedding' => 0,
                        'console' => 0,
                    ],
                    'last_errors' => [],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // 6️⃣ Процессы
            ['key' => 'console_script_runs', 'counter_type' => 'delta', 'value' => json_encode(['count' => 0]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'last_console_script_run', 'counter_type' => 'total', 'value' => json_encode(['timestamp' => null]), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_counters');
    }
};
