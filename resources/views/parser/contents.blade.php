<x-app-layout title="📄 Parsed Articles">
<div class="max-w-7xl mx-auto py-10 px-6">
    <h2 class="text-3xl font-bold text-white mb-6">📄 Parsed Articles</h2>

    @if(isset($savedCount))
        <div class="mb-4 font-medium text-sm text-green-600">
            Saved articles: {{ $savedCount }}
        </div>
    @endif

    @if (isset($error))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            {{ $error }}
        </div>
    @endif

    @isset($articles)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($articles as $article)
                <div class="bg-white text-black rounded-xl shadow-lg overflow-hidden flex flex-col">
                    <div class="p-4 flex flex-col flex-grow">
                        <h3 class="text-lg font-semibold mb-2">
                            {{ $article['title'] ?? 'Title not found' }}
                        </h3>

                        <p class="text-sm text-gray-500 mb-2">
                            <a href="{{ $article['url'] }}" class="text-blue-600 hover:underline" target="_blank">
                                🔗 Open source
                            </a>
                        </p>

                        @if (!empty($article['excerpt']))
                            <p class="text-gray-700 text-sm">
                                <strong>Summary:</strong> {{ $article['excerpt'] }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-white">No data to display.</p>
    @endisset
</div>
</x-app-layout>
