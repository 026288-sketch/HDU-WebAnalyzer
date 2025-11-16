<x-app-layout title="📚 Saved Articles">
<div class="max-w-7xl mx-auto py-10 px-6">
    <h2 class="text-3xl font-bold text-white mb-6">📚 Saved Articles</h2>

    @if (session('success'))
        <div class="text-green-500 font-medium mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if ($articles->count())
        {{-- Clear All Button --}}
        <form method="POST" action="{{ route('articles.clear') }}" onsubmit="return confirm('Are you sure you want to delete all saved articles?')">
            @csrf
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded mb-6">
                🗑 Clear All
            </button>
        </form>

        {{-- Article Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($articles as $article)
                <div class="bg-white text-black rounded-xl shadow p-4 flex flex-col justify-between">
                    <div>
                        {{-- Image --}}
                        @if (!empty($article->image))
                            <img src="{{ $article->image }}" alt="Article Image" class="w-full h-40 object-cover rounded mb-3">
                        @endif

                        {{-- Title --}}
                        <h3 class="text-lg font-semibold mb-2">{{ $article->title }}</h3>

                        {{-- Summary --}}
                        @if (!empty($article->summary))
                            <p class="text-sm mb-2">
                                <strong>Summary:</strong> {{ $article->summary }}
                            </p>
                        @endif

                        {{-- Published Date --}}
                        <p class="text-sm text-gray-600 mb-2">
                            <strong>Published At:</strong>
                            {{ \Carbon\Carbon::parse($article->timestamp)->format('d.m.Y H:i') }}
                        </p>

                        {{-- View Original --}}
                        <p class="text-sm text-gray-500 mb-2">
                            <a href="{{ $article->url }}" class="text-blue-600 hover:underline" target="_blank">
                                🔗 View Original
                            </a>
                        </p>
                    </div>

                    {{-- Read More (Full Article) --}}
                    <div class="mt-auto">
                        <a href="{{ route('articles.show', $article->id) }}"
                           class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-4 rounded text-center w-full">
                            📖 Read Full Article
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $articles->links() }}
        </div>
    @else
        <p class="text-white">No saved articles found.</p>
    @endif
</div>
</x-app-layout>
