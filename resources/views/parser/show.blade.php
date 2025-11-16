<x-app-layout :title="$article->title">
<div class="max-w-4xl mx-auto py-10 px-6 text-white">

    {{-- Flash message --}}
    @if(session('success'))
        <div class="bg-green-600 text-white px-4 py-2 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    {{-- Back --}}
    <div class="mb-6">
        <a href="{{ route('articles.index') }}" class="text-gray-300 hover:text-white transition duration-200">&larr; Back to the list</a>
    </div>

    {{-- Title --}}
    <h1 class="text-3xl font-bold mb-4">{{ $article->title }}</h1>

    @if($article->tags->count())
    <div class="mt-4">
        <h4 class="text-gray-300 mb-2">Tags:</h4>
        @foreach($article->tags as $tag)
            <a href="{{ route('tags.show', $tag->slug) }}"
               class="inline-block bg-gray-700 text-white px-2 py-1 rounded-lg text-sm mr-2 hover:bg-blue-600 transition">
                {{ $tag->name }}
            </a>
        @endforeach
    </div>
    @endif

    @if($article->sentiment)
    <div class="mt-6 p-4 bg-gray-800 rounded-lg border border-gray-700">
        <h3 class="text-lg font-semibold mb-2 text-white">🧭 Text Analysis</h3>
        <div class="flex flex-col sm:flex-row gap-4">
            <div>
                <p class="text-gray-300">
                    <strong>Sentiment:</strong>
                    <a href="{{ route('sentiments.show', ['type' => 'sentiment', 'value' => $article->sentiment->sentiment]) }}"
                       class="text-yellow-400 hover:text-yellow-300 underline">
                        {{ ucfirst($article->sentiment->sentiment) }}
                    </a>
                </p>
            </div>
            <div>
                <p class="text-gray-300">
                    <strong>Emotion:</strong>
                    <a href="{{ route('sentiments.show', ['type' => 'emotion', 'value' => $article->sentiment->emotion]) }}"
                       class="text-green-400 hover:text-green-300 underline">
                        {{ ucfirst($article->sentiment->emotion) }}
                    </a>
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Summary --}}
    @if($article->summary)
        <p class="text-gray-300 mb-6">{{ $article->summary }}</p>
    @endif

    {{-- Image --}}
    @if($article->image)
        <img src="{{ $article->image }}" alt="Image" class="w-full rounded mb-6">
    @endif

    {{-- Content --}}
    <div class="prose prose-invert max-w-full">
        {!! $article->content !!}
    </div>

    {{-- Edit/Delete --}}
    <div class="mt-8 flex gap-2">
        <a href="{{ route('articles.edit', $article->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Edit</a>

        <form action="{{ route('articles.destroy', $article->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this article?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Delete</button>
        </form>
    </div>

</div>
</x-app-layout>
