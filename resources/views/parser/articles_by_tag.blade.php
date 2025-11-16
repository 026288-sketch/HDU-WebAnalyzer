<x-app-layout :title="'🧩 Articles with tag: '.$tag->name">
<div class="max-w-4xl mx-auto py-10 px-6 text-white">

    <h1 class="text-3xl font-bold mb-6">
        🏷️ Articles with tag: <span class="text-blue-400">{{ $tag->name }}</span>
    </h1>

    @if ($articles->count())
        <div class="space-y-6">
            @foreach ($articles as $article)
                <div class="bg-gray-800 rounded-xl p-5 shadow hover:shadow-lg transition">
                    <a href="{{ route('articles.show', $article->id) }}" class="text-xl font-semibold text-blue-400 hover:underline">
                        {{ $article->title }}
                    </a>
                    <p class="text-gray-400 mt-2">
                        {{ \Illuminate\Support\Str::limit(strip_tags($article->summary ?? $article->content), 150) }}
                    </p>
                    <p class="text-sm text-gray-500 mt-2">
                        🕓 {{ $article->timestamp?->format('d.m.Y H:i') ?? 'No date' }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $articles->links() }}
        </div>
    @else
        <p class="text-gray-400">There are no articles with this tag.</p>
    @endif

</div>
</x-app-layout>
