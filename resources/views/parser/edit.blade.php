<x-app-layout title="Edit Article">
<div class="max-w-4xl mx-auto py-10 px-6 text-white">
    <h1 class="text-2xl font-bold mb-6">Edit Article</h1>

    <form action="{{ route('articles.update', $article->id) }}" method="POST">
        @csrf
        @method('PATCH')

        {{-- Title --}}
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title', $article->title) }}" class="w-full border rounded px-3 py-2 bg-gray-800 text-white">
            @error('title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Summary --}}
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Short Description</label>
            <textarea name="summary" rows="3" class="w-full border rounded px-3 py-2 bg-gray-800 text-white">{{ old('summary', $article->summary) }}</textarea>
            @error('summary')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="tags" class="block text-gray-200">Tags (comma-separated):</label>
            <input type="text" id="tags" name="tags"
                   value="{{ old('tags', $article->tags->pluck('name')->implode(', ')) }}"
                   class="w-full rounded-lg bg-gray-800 text-white p-2 border border-gray-700">
        </div>

        {{-- Content --}}
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Content</label>
            <textarea name="content" rows="10" class="w-full border rounded px-3 py-2 bg-gray-800 text-white">{{ old('content', $article->content) }}</textarea>
            @error('content')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Image --}}
        <div class="mb-6">
            <label class="block mb-2 font-semibold">Image URL</label>
            <input type="text" name="image" value="{{ old('image', $article->image) }}" class="w-full border rounded px-3 py-2 bg-gray-800 text-white">
            @error('image')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Save</button>
    </form>
</div>
</x-app-layout>
