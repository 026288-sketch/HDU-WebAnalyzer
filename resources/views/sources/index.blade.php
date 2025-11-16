<x-app-layout title="Sources">
<div class="max-w-6xl mx-auto py-10 px-6">
    <h2 class="text-3xl font-bold text-white mb-6">🔗 Sources</h2>

    <form method="POST" action="{{ route('sources.store') }}" class="flex flex-col md:flex-row items-center gap-4 mb-8">
        @csrf
        <input
            type="url"
            name="url"
            placeholder="https://example.com"
            required
            class="w-full md:max-w-md px-4 py-2 border border-gray-300 rounded shadow"
        >
        <button
            type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded shadow transition"
        >
            + Add Source
        </button>
    </form>

    <div class="overflow-x-auto bg-white shadow-md rounded-lg w-full">
        <table class="w-full text-sm text-gray-800">
            <thead class="bg-gray-200 text-gray-600 uppercase text-xs font-semibold">
                <tr>
                    <th class="py-3 px-4 text-left w-1/12">#</th>
                    <th class="py-3 px-4 text-left">URL</th>
                    <th class="py-3 px-4 text-left">RSS URL</th>
                    <th class="py-3 px-4 text-left">NEED BROWSER</th>
                    <th class="py-3 px-4 text-left w-1/12">Active</th>
                    <th class="py-3 px-4 text-left w-1/12">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sources as $index => $source)
                    <tr class="border-b hover:bg-gray-100">
                        <td class="py-3 px-4">{{ $index + 1 }}</td>
                        <td class="py-3 px-4">{{ $source->url }}</td>
                        <td class="py-3 px-4"><a href="{{ $source->rss_url }}" target="_blank" class="text-blue-500 hover:text-blue-700">Check</a></td>
                        <td class="py-3 px-4">{{ $source->need_browser ? 'true' : 'false' }}</td>
                        <td class="py-3 px-4">
                            @if ($source->isActive)
                                <span class="text-green-600 font-semibold">✓</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <form action="{{ route('sources.destroy', $source) }}" method="POST" onsubmit="return confirm('Delete this source?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline font-semibold">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-gray-500">No sources added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>
