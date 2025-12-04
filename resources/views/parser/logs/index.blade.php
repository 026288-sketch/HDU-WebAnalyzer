<x-app-layout title="🧾 Parser log">
    <x-slot name="header">
    </x-slot>
<div class="max-w-6xl mx-auto py-10 px-6">
    <h2 class="text-3xl font-bold text-white mb-6">🧾 Parser log</h2>

    <div class="overflow-x-auto bg-white shadow-md rounded-lg w-full">
        <table class="w-full text-sm text-gray-800">
            <thead class="bg-gray-200 text-gray-600 uppercase text-xs font-semibold">
                <tr>
                    <th class="py-3 px-4 text-left w-1/6">Time</th>
                    <th class="py-3 px-4 text-left w-1/12">Level</th>
                    <th class="py-3 px-4 text-left w-1/6">Service</th>
                    <th class="py-3 px-4 text-left w-1/3">Message</th>
                    <th class="py-3 px-4 text-left">Context</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-b hover:bg-gray-100 align-top">
                        <td class="py-3 px-4">
                            {{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '—' }}
                        </td>
                        <td class="py-3 px-4 font-bold {{ $log->level === 'error' ? 'text-red-600' : ($log->level === 'success' ? 'text-green-600' : 'text-blue-600') }}">
                            {{ strtoupper($log->level) }}
                        </td>
                        <td class="py-3 px-4">{{ $log->service }}</td>
                        <td class="py-3 px-4">{{ $log->message }}</td>
                        <td class="py-3 px-4">
                            @php
                                $context = is_array($log->context) ? $log->context : json_decode($log->context, true);
                            @endphp

                            @if (is_array($context) && count($context))
                                <table class="mt-1 border border-gray-300 w-full text-xs">
                                    @foreach ($context as $key => $value)
                                        <tr class="border-b">
                                            <td class="px-2 py-1 font-semibold bg-gray-100 w-1/4">{{ $key }}</td>
                                            <td class="px-2 py-1">
                                                @if (is_array($value) || is_object($value))
                                                    <pre>{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                @elseif (is_bool($value)) {{ $value ? 'true' : 'false' }}
                                                @elseif (filter_var($value, FILTER_VALIDATE_URL))
                                                    <a href="{{ $value }}" target="_blank" class="text-blue-600 hover:underline">{{ $value }}</a>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @elseif($log->context)
                                <div class="text-gray-600 text-xs">{{ $log->context }}</div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-gray-500">The log is empty.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $logs->links('pagination::tailwind') }}
    </div>

    <form action="{{ route('parser.logs.clear') }}" method="POST" onsubmit="return confirm('Clear log?')" class="mt-6 text-right">
        @csrf
        @method('DELETE')
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded shadow inline-flex items-center">
            🗑 Clear log
        </button>
    </form>
</div>
</x-app-layout>
