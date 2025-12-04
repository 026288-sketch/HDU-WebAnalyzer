<x-app-layout title="Панель статистики">
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
            Статистика та аналітика парсера
        </h2>
    </x-slot>

    <div class="py-10 px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-10 text-gray-900 dark:text-gray-100">

            {{-- 🟢 Service status --}}
<section>
    <h3 class="text-xl font-bold mb-6 text-indigo-500">Статус сервісів</h3>
    
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    {{-- Puppeteer --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🎭</span>
                <div>
                    <h4 class="font-semibold text-lg">Puppeteer Server</h4>
                    <p class="text-xs text-gray-500">http://127.0.0.1:3000</p>
                </div>
            </div>
            <div id="puppeteer-status-badge" class="px-3 py-1 rounded-full text-sm font-semibold bg-gray-300 text-gray-700">
                Перевірка...
            </div>
        </div>
        
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Статус:</span>
                <span id="puppeteer-message" class="font-medium">—</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Час відповіді:</span>
                <span id="puppeteer-response-time" class="font-medium">—</span>
            </div>
        </div>
    </div>
    
    {{-- Python FastAPI --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🐍</span>
                <div>
                    <h4 class="font-semibold text-lg">Python Service</h4>
                    <p class="text-xs text-gray-500">http://127.0.0.1:8000</p>
                </div>
            </div>
            <div id="python-status-badge" class="px-3 py-1 rounded-full text-sm font-semibold bg-gray-300 text-gray-700">
                Перевірка...
            </div>
        </div>
        
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Статус:</span>
                <span id="python-message" class="font-medium">—</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Час відповіді:</span>
                <span id="python-response-time" class="font-medium">—</span>
            </div>
        </div>
    </div>

</div>

</section>

{{-- 🟩 1. General overview --}}
<section>
    <h3 class="text-xl font-bold mb-6 text-indigo-500">1. Загальний огляд</h3>
    
    {{-- Row 1: 4 basic cards--}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 text-center">
            <p class="text-lg font-semibold">📰 Всього статей</p>
            <p class="text-3xl font-bold mt-2 text-indigo-600">{{ number_format($totalNodes) }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 text-center">
            <p class="text-lg font-semibold">📎 Необроблені посилання</p>
            <p class="text-3xl font-bold mt-2 text-indigo-600">{{ number_format($unparsedLinks) }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 text-center">
            <p class="text-lg font-semibold">🏷️ Всього тегів</p>
            <p class="text-3xl font-bold mt-2 text-indigo-600">{{ number_format($totalTags) }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 text-center">
            <p class="text-lg font-semibold">🌐 Джерела</p>
            <p class="text-3xl font-bold mt-2 text-indigo-600">{{ $totalSources }}</p>
            <p class="text-sm text-gray-500 mt-1">
                RSS: {{ $rssSources }} / HTML: {{ $totalSources - $rssSources }}
            </p>
        </div>
    </div>
    
    {{-- Row 2: 2 cards in the center --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 text-center">
            <p class="text-lg font-semibold">📊 Парсинг</p>
            <p class="text-3xl font-bold mt-2 text-indigo-600">{{ number_format($nodesParsed) }}</p>
            <p class="text-sm text-gray-500 mt-1">
                Дублікати: {{ $nodesDuplicates }} / Помилки: {{ $totalErrors }}
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 text-center">
            <p class="text-lg font-semibold">🔧 Консольні команди</p>
            <p class="text-3xl font-bold mt-2 text-indigo-600">{{ $consoleCommands ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Запусків сьогодні</p>
        </div>
    </div>
</section>

            {{-- 🟨 2. Distribution graphs --}}
            <section>
                <h3 class="text-xl font-bold mb-6 text-indigo-500">2. Графіки розподілу</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">🎭 Розподіл емоцій</h4>
                        <div class="space-y-2">
                            @foreach($emotions as $emotion => $count)
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="capitalize">{{ $emotion }}</span>
                                        <span>{{ $count }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $count > 0 ? ($count / array_sum($emotions) * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">📊 Тональність статей</h4>
                        <div class="space-y-2">
                            @foreach($sentiment as $type => $count)
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="capitalize">{{ $type }}</span>
                                        <span>{{ $count }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-{{ $type === 'positive' ? 'green' : ($type === 'negative' ? 'red' : 'gray') }}-600 h-2 rounded-full" 
                                             style="width: {{ $count > 0 ? ($count / array_sum($sentiment) * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 lg:col-span-2">
    <h4 class="font-semibold text-lg mb-4">🌐 Типи джерел</h4>
    <div class="flex justify-around items-center h-32">
        <div class="text-center">
            <p class="text-4xl font-bold text-blue-600">{{ $rssSources }}</p>
            <p class="text-gray-500">RSS</p>
        </div>
        <div class="text-center">
            <p class="text-4xl font-bold text-green-600">{{ $fullRssSources }}</p>
            <p class="text-gray-500">FULL RSS Contents</p>
        </div> 
        <div class="text-center">
            <p class="text-4xl font-bold text-orange-600">{{ $totalSources - $rssSources }}</p>
            <p class="text-gray-500">HTML</p>
        </div>                                                       
        <div class="text-center">
            <p class="text-4xl font-bold text-red-600">{{ $browserSources }}</p>
            <p class="text-gray-500">Browser required</p>
        </div>
    </div>
</div>

                </div>
            </section>

            {{-- 🟦 3. Errors and statuses --}}
            <section>
                <h3 class="text-xl font-bold mb-6 text-indigo-500">3. Помилки та статуси</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">❌ Статистика помилок</h4>
                        <div class="space-y-2">
                            @foreach($errorTypes as $type => $count)
                                <div class="flex justify-between">
                                    <span class="capitalize">{{ $type }}</span>
                                    <span class="font-bold">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                        
    @if(count($lastErrors) > 0)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="font-semibold mb-2">Останні помилки:</p>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                @foreach(array_slice($lastErrors, -3) as $error)
                    <li class="border-l-2 border-red-500 pl-2">
                        <div class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $error['message'] ?? 'Невідома помилка' }}
                        </div>
                        @if(isset($error['service']))
                            <div class="text-xs mt-1">
                                Сервіс: {{ $error['service'] }}
                            </div>
                        @endif
                        @if(isset($error['timestamp']))
                            <div class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($error['timestamp'])->format('d.m.Y H:i:s') }}
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">⚙️ Статистика парсингу</h4>
                        <ul class="space-y-3 text-lg">
                            <li class="flex justify-between">
                                <span class="font-semibold">Оброблено:</span>
                                <span class="text-green-500">{{ number_format($nodesParsed) }}</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="font-semibold">Дублікати:</span>
                                <span class="text-yellow-500">{{ number_format($nodesDuplicates) }}</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="font-semibold">Помилки:</span>
                                <span class="text-red-500">{{ number_format($totalErrors) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
            
            {{-- 🟪 4. Popular tags  --}}
            <section>
                <h3 class="text-xl font-bold mb-6 text-indigo-500">4. Популярні теги</h3>

                {{-- 🌥️ Tag cloud  --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 mb-8 overflow-hidden">
                    <h4 class="font-semibold text-lg mb-4">🌥️ Облако тегів (50)</h4>

                    @php
                        $counts = $popularTags->pluck('usage_count');
                        $min_count = $counts->min() ?? 1;
                        $max_count = $counts->max() ?? 1;
                        $log_min = log($min_count);
                        $log_max = log($max_count);
                        $spread = ($log_max - $log_min) == 0 ? 1 : ($log_max - $log_min);
                        $min_size = 0.8;  // ~text-sm
                        $max_size = 1.75; // ~text-2xl
                        $size_range = $max_size - $min_size;
                    @endphp

                    <div class="flex flex-wrap gap-x-4 gap-y-2 items-baseline">
{{-- 
gap-x-4: Horizontal indentation 
gap-y-2: Vertical indentation 
items-baseline: Aligns tags of different sizes to their text "baseline". 
--}}

                        @foreach($popularTags as $tag)

                            @php
                                
                                $current_count = $tag->usage_count ?? 1;

                                $weight = (log($current_count) - $log_min) / $spread;

                                $size = $min_size + ($weight * $size_range);
                            @endphp
                            
                            <a href="/tags/{{ $tag->slug ?? $tag->name }}"
                               class="font-medium text-gray-600 dark:text-gray-400 
                                      hover:text-blue-600 dark:hover:text-blue-400
                                      transition-colors duration-200 whitespace-nowrap
                                      leading-tight"
                               style="font-size: {{ $size }}rem;">
                                {{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- 📊 Top 10 tags --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                    <h4 class="font-semibold text-lg mb-4">📊 Топ-10 тегів за кількістю статей</h4>
                    <div class="space-y-2">
                        @php
                            $max = $top10Tags->max('usage_count') ?: 1;
                        @endphp
                        @foreach($top10Tags as $tag)
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span>#{{ $tag->name }}</span>
                                    <span>{{ $tag->usage_count }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ ($tag->usage_count / $max) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- 🟪 5. Dynamics over the period--}}
            <section>
                <h3 class="text-xl font-bold mb-6 text-indigo-500">5. Динаміка за період</h3>
                
                {{-- Advanced filters --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 mb-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        {{-- Quick filters --}}
                        <div>
                            <label class="block text-sm font-semibold mb-3">Швидкий вибір періоду:</label>
                            <div class="flex flex-wrap gap-3">
                                <button data-days="7" class="chart-period-btn px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">7 днів</button>
                                <button data-days="14" class="chart-period-btn px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">14 днів</button>
                                <button data-days="30" class="chart-period-btn px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition ring-2 ring-indigo-300">30 днів</button>
                                <button data-days="90" class="chart-period-btn px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">90 днів</button>
                            </div>
                        </div>
                        
                        {{-- Custom period --}}
                        <div>
                            <label class="block text-sm font-semibold mb-3">Або оберіть власний період:</label>
                            <div class="flex gap-3 items-end">
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Від:</label>
                                    <input type="date" id="dateFrom" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">До:</label>
                                    <input type="date" id="dateTo" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                </div>
                                <button id="applyCustomPeriod" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                                    Застосувати
                                </button>
                            </div>
                        </div>
                        
                    </div>
                    
                    {{-- Grouping switch --}}
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-semibold mb-3">Групування даних:</label>
                        <div class="flex gap-3">
                            <button data-group="daily" class="chart-group-btn px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition ring-2 ring-indigo-400">
                                📅 По днях
                            </button>
                            <button data-group="monthly" class="chart-group-btn px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                📆 По місяцях
                            </button>
                        </div>
                    </div>
                </div>
                

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">📈 Парсинг</h4>
                        <div style="height: 300px;">
                            <canvas id="parsingChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">📊 Тональність</h4>
                        <div style="height: 300px;">
                            <canvas id="sentimentChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">🎭 Емоції</h4>
                        <div style="height: 300px;">
                            <canvas id="emotionsChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">❌ Помилки</h4>
                        <div style="height: 300px;">
                            <canvas id="errorsChart"></canvas>
                        </div>
                    </div>


                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">🔄 Дублікати</h4>
                        <div style="height: 300px;">
                            <canvas id="duplicatesChart"></canvas>
                        </div>
                    </div>
                 
<div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
    <h4 class="font-semibold text-lg mb-4">🖥️ Консольні скрипти</h4>
    <div style="height: 300px;">
        <canvas id="consoleCommandsChart"></canvas>
    </div>
</div>            
                </div>
            </section>
            
            {{-- 🟧 6. Data export --}}
            <section>
                <h3 class="text-xl font-bold mb-6 text-indigo-500">6. Експорт даних</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    {{-- Export nodes --}}
                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">📰 Експорт статей (Nodes)</h4>
                        
                        <form action="{{ route('export.nodes') }}" method="POST">
                            @csrf
                            
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Оберіть поля для експорту:</p>
                            
                            <div class="space-y-2 mb-6">
                                <label class="flex items-center">
                                    <input type="checkbox" name="fields[]" value="timestamp" checked class="mr-2 rounded">
                                    <span>Дата/Час</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fields[]" value="title" checked class="mr-2 rounded">
                                    <span>Заголовок</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fields[]" value="summary" checked class="mr-2 rounded">
                                    <span>Резюме</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fields[]" value="content" class="mr-2 rounded">
                                    <span>Контент (повний текст)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fields[]" value="url" checked class="mr-2 rounded">
                                    <span>URL</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fields[]" value="image" class="mr-2 rounded">
                                    <span>Зображення</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fields[]" value="sentiment" checked class="mr-2 rounded">
                                    <span>Тональність</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fields[]" value="emotion" checked class="mr-2 rounded">
                                    <span>Емоція</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fields[]" value="hash" class="mr-2 rounded">
                                    <span>Hash</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fields[]" value="tags" class="mr-2 rounded">
                                    <span>Теги</span>
                                </label>
                            </div>
                            
                            <button type="submit" class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                                ⬇️ Завантажити Excel
                            </button>
                        </form>
                    </div>
                    
                    {{-- Export statistics --}}
                    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                        <h4 class="font-semibold text-lg mb-4">📊 Експорт статистики</h4>
                        
                        <form action="{{ route('export.stats') }}" method="POST">
                            @csrf
                            
                            <div class="space-y-4 mb-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Період:</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Від:</label>
                                            <input type="date" name="date_from" id="exportDateFrom" 
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">До:</label>
                                            <input type="date" name="date_to" id="exportDateTo" 
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Групування:</label>
                                    <select name="group" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                        <option value="daily">По днях</option>
                                        <option value="monthly">По місяцях</option>
                                    </select>
                                </div>
                                
                                <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded text-sm">
                                    <p class="font-semibold mb-1">Експортуються дані:</p>
                                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                        <li>✓ Оброблені статті</li>
                                        <li>✓ Тональність (позитив/негатив/нейтрал)</li>
                                        <li>✓ Всі емоції (7 типів)</li>
                                        <li>✓ Помилки</li>
                                        <li>✓ Дублікати</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                                ⬇️ Завантажити статистику Excel
                            </button>
                        </form>
                    </div>
                    
                </div>
            </section>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.chartData = @json($chartData);
</script>
</x-app-layout>