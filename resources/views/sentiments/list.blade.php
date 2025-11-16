<x-app-layout :title="$title">
<div class="max-w-4xl mx-auto py-10 px-6 text-white">

    <h1 class="text-3xl font-bold mb-6">{{ $title }}</h1>

    @if ($articles->isEmpty())
        <p class="text-gray-400">No articles with this {{ $type === 'sentiment' ? 'sentiment' : 'emotion' }}.</p>
    @else
        <div class="space-y-8">
            @foreach ($articles as $article)
                <article class="border-b border-gray-700 pb-4">
                    <h2 class="text-2xl font-semibold mb-2">
                        <a href="{{ route('articles.show', $article->id) }}" class="hover:text-blue-400">
                            {{ $article->title }}
                        </a>
                    </h2>
                    <p class="text-gray-300 mb-2">{{ $article->summary }}</p>

                    <div class="text-sm text-gray-400">
                        {{ $article->created_at->format('d.m.Y') }}
                        @if ($article->sentiment)
                            • <span class="italic">Sentiment: {{ $article->sentiment->sentiment }}</span>
                            @if ($article->sentiment->emotion)
                                , <span class="italic">Emotion: {{ $article->sentiment->emotion }}</span>
                            @endif
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

</div>
</x-app-layout>
