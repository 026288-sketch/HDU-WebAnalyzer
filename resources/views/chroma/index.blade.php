<x-app-layout title="🧩 ChromaDB GUI">
    <x-slot name="header">
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 px-4 text-white">

        <h1 class="text-3xl font-bold mb-6">🧩 ChromaDB GUI</h1>

        {{-- Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-800 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-indigo-400">{{ $totalArticles }}</p>
                <p class="text-sm text-gray-400">Total Articles</p>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-indigo-400">{{ $totalChunks }}</p>
                <p class="text-sm text-gray-400">Total Chunks</p>
            </div>
            <div class="bg-gray-800 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-indigo-400">{{ $paginatedItems->perPage() }}</p>
                <p class="text-sm text-gray-400">Per Page</p>
            </div>
        </div>

        {{-- Messages --}}
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-600 text-white rounded-lg shadow">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-600 text-white rounded-lg shadow">
                ❌ {{ session('error') }}
            </div>
        @endif

        {{-- Text check form --}}
        <div class="mb-6 bg-gray-800 rounded-lg p-6 shadow">
            <div class="mb-4 p-3 bg-blue-900 text-blue-100 rounded border border-blue-500">
                <strong>ℹ️ Information:</strong> Text checking is performed <span class="font-semibold">without saving to the database</span>.
                The result will be displayed below.
            </div>
            
            <form action="{{ route('chroma.check') }}" method="POST">
                @csrf
                <textarea name="content" rows="5" class="w-full p-3 bg-gray-900 text-white border border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500" 
                          placeholder="Enter text to check for duplicates..."></textarea>
                <button type="submit" class="mt-3 px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-semibold">
                    🔍 Check for Duplicate
                </button>
            </form>

            @if(session('result'))
                <div class="mt-4 p-4 bg-gray-900 rounded-lg border border-gray-700">
                    <pre class="whitespace-pre-wrap break-words text-sm text-gray-300">{{ json_encode(session('result'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
        </div>

        {{-- Warning --}}
        <div class="mb-6 p-4 bg-yellow-700 text-yellow-100 rounded-lg border border-yellow-500 shadow">
            <strong>⚠️ Warning!</strong> Deleting one or more chunks will automatically delete the <span class="font-semibold">entire article</span> (all related chunks) including:
            <ul class="list-disc list-inside mt-2 space-y-1">
                <li>🗞️ the article (<code>nodes</code>)</li>
                <li>🔗 article link (<code>node_links</code>)</li>
                <li>📊 embeddings (<code>node_embeddings</code>)</li>
                <li>📦 all article chunks in ChromaDB</li>
            </ul>
            This action <span class="font-semibold text-red-300">cannot be undone</span>.
        </div>

        {{-- Document list --}}
        <div class="bg-gray-800 rounded-lg shadow overflow-hidden">
            <form action="{{ route('chroma.delete') }}" method="POST">
                @csrf

                {{-- Table header with button --}}
                <div class="p-4 bg-gray-900 border-b border-gray-700 flex justify-between items-center">
                    <div class="text-sm text-gray-400">
                        Showing {{ $paginatedItems->firstItem() ?? 0 }}-{{ $paginatedItems->lastItem() ?? 0 }} of {{ $paginatedItems->total() }}
                    </div>
                    <button type="submit" 
                            onclick="return confirm('⚠️ Delete selected articles with all chunks?\n\nThis action cannot be undone!')"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition font-semibold">
                        🗑️ Delete Selected
                    </button>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-900 text-gray-300 text-sm">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input type="checkbox" id="select-all" class="rounded" title="Select all on this page">
                                </th>
                                <th class="px-4 py-3 text-left">Parent ID</th>
                                <th class="px-4 py-3 text-left">Chunks</th>
                                <th class="px-4 py-3 text-left">Preview of First Chunk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @forelse($paginatedItems as $item)
                                <tr class="hover:bg-gray-700 transition">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" 
                                               name="ids[]" 
                                               value="{{ $item['chunks'][0]['id'] }}" 
                                               class="select-item rounded"
                                               title="Delete article ({{ $item['total_chunks'] }} chunks)">
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-mono text-sm text-gray-300">
                                            {{ Str::limit($item['parent_id'], 40) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 bg-indigo-600 text-white rounded-full text-xs font-semibold">
                                            {{ $item['total_chunks'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-400">
                                        {{ Str::limit($item['first_chunk_preview'], 120) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-gray-500">
                                        <div class="text-6xl mb-4">📭</div>
                                        <p class="text-lg">No data in ChromaDB</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($paginatedItems->hasPages())
                    <div class="p-4 bg-gray-900 border-t border-gray-700">
                        {{ $paginatedItems->links() }}
                    </div>
                @endif
            </form>
        </div>

        {{-- Additional info --}}
        <div class="mt-6 p-4 bg-gray-800 rounded-lg text-sm text-gray-400">
            <p><strong>💡 Tips:</strong></p>
            <ul class="list-disc list-inside mt-2 space-y-1">
                <li>Parent ID – unique article identifier in ChromaDB</li>
                <li>One article can be split into multiple chunks for efficient search</li>
                <li>Deleting any chunk deletes the whole article</li>
                <li>To change the number of items per page, add <code>?per_page=50</code> to the URL</li>
            </ul>
        </div>
    </div>

    @push('scripts')
    <script>
        // Select all checkboxes on current page
        document.getElementById('select-all')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.select-item');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Confirmation on delete
        const form = document.querySelector('form[action="{{ route('chroma.delete') }}"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const checked = document.querySelectorAll('.select-item:checked');
                if (checked.length === 0) {
                    e.preventDefault();
                    alert('❌ Select at least one article to delete');
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
