<x-app-layout title="Parser Testing">
<div class="max-w-7xl mx-auto py-10 px-6">
    <h2 class="text-3xl font-bold text-white mb-6">🔗 Link Parser</h2>

    @if (isset($error))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            {{ $error }}
        </div>
    @endif

    @isset($source)
        <div class="bg-white text-black rounded-lg p-4 shadow mb-6">
            <strong>Source URL:</strong> {{ $source->url }}
        </div>
    @endisset

    <form action="{{ route('parser.links.run') }}" method="POST" class="text-right mt-6">
        @csrf
        <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow inline-flex items-center">
            ▶ Start links parsing
        </button>
    </form>

    @isset($links)
        <div class="mt-6 text-white">
            <h3 class="text-xl font-semibold mb-2">🔍 Parsing Summary</h3>
            <p><strong>Regex used:</strong> <code class="text-yellow-300">{{ $regex ?? '—' }}</code></p>
            <p><strong>Total links found:</strong> <span class="text-green-400">{{ count($links) }}</span></p>
        </div>

        <div class="overflow-x-auto bg-white shadow-md rounded-lg w-full mt-4">
            <table class="w-full text-sm text-gray-800">
                <thead class="bg-gray-200 text-gray-600 uppercase text-xs font-semibold">
                    <tr>
                        <th class="py-3 px-4 text-left">Link</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($links as $link)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="py-3 px-4">
                                <a href="{{ $link['url'] }}" class="text-blue-600 hover:underline" target="_blank">
                                    {{ $link['title'] ?? $link['url'] }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center py-4 text-gray-500">No links found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (count($links) > 0)
            <form action="{{ route('parser.contents') }}" method="POST" class="mt-6 text-right">
                @csrf
                @foreach($links as $link)
                    <input type="hidden" name="urls[]" value="{{ $link['url'] }}">
                @endforeach

                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded shadow inline-flex items-center">
                    📥 Parse Articles
                </button>
            </form>
        @endif
    @endisset

    @isset($html)
        <div class="mt-8">
            <h3 class="text-xl font-semibold text-white mb-4">🔧 HTML Inspector</h3>

            <!-- Chrome DevTools-like Inspector -->
            <div class="bg-[#202124] rounded-lg overflow-hidden shadow-2xl border border-gray-700">
                
                <!-- Header Toolbar -->
                <div class="bg-[#2d2e30] border-b border-gray-700 px-4 py-2 flex items-center justify-between">
                    <div class="text-xs text-white font-semibold">
                        HTML Inspector
                    </div>
                    <div class="flex items-center space-x-3 text-xs text-gray-400">
                        <span>📄 {{ count(explode("\n", $html ?? '')) }} lines</span>
                        <span>💾 {{ round(strlen($html ?? '') / 1024, 2) }} KB</span>
                        <button onclick="copyHTML()" id="copyBtn"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition text-xs font-semibold ml-2">
                            📋 Copy
                        </button>
                    </div>
                </div>

                <!-- HTML Content with Line Numbers -->
                <div class="flex bg-[#202124] overflow-hidden" style="height: 600px;">
                    <!-- Line Numbers -->
                    <div class="bg-[#2d2e30] text-gray-500 text-xs font-mono select-none border-r border-gray-700 overflow-y-auto" id="lineNumbers">
                        @php
                            $htmlLines = explode("\n", $html ?? '');
                        @endphp
                        @foreach($htmlLines as $lineIndex => $line)
                            <div class="px-3 py-1 text-right hover:bg-[#37373d] transition">{{ $lineIndex + 1 }}</div>
                        @endforeach
                    </div>

                    <!-- HTML Code with Syntax Highlighting -->
                    <div class="flex-1 overflow-auto font-mono text-sm" id="codeContainer">
                        @foreach($htmlLines as $lineIndex => $line)
                            @php
                                // Preserve original line for display
                                $escapedLine = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                
                                // Calculate indent
                                $indent = strlen($line) - strlen(ltrim($line));
                                $trimmedLine = ltrim($line);
                                $escapedTrimmed = htmlspecialchars($trimmedLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                
                                // Simple syntax highlighting - applied in order, no overlaps
                                $highlighted = $escapedTrimmed;
                                
                                // 1. Comments (green)
                                $highlighted = preg_replace(
                                    '/(&lt;!--.*?--&gt;)/',
                                    '<span style="color:#6a9955;">$1</span>',
                                    $highlighted
                                );
                                
                                // 2. DOCTYPE (blue)
                                $highlighted = preg_replace(
                                    '/(&lt;!DOCTYPE[^&]*?&gt;)/',
                                    '<span style="color:#569cd6;">$1</span>',
                                    $highlighted
                                );
                                
                                // 3. Opening/closing tags (cyan tag names, gray brackets)
                                $highlighted = preg_replace_callback(
                                    '/&lt;(\/?)([a-zA-Z0-9\-:]+)(.*?)&gt;/',
                                    function($matches) {
                                        $openBracket = '&lt;';
                                        $slash = $matches[1];
                                        $tagName = $matches[2];
                                        $rest = $matches[3];
                                        $closeBracket = '&gt;';
                                        
                                        // Highlight attributes in $rest
                                        $rest = preg_replace(
                                            '/([a-zA-Z0-9:\-]+)=(&quot;[^&]*?&quot;)/',
                                            '<span style="color:#9cdcfe;">$1</span>=<span style="color:#ce9178;">$2</span>',
                                            $rest
                                        );
                                        
                                        return '<span style="color:#808080;">' . $openBracket . $slash . '</span>' .
                                               '<span style="color:#4ec9b0;">' . $tagName . '</span>' .
                                               $rest .
                                               '<span style="color:#808080;">' . $closeBracket . '</span>';
                                    },
                                    $highlighted
                                );
                            @endphp
                            <div class="hover:bg-[#2a2d2e] transition group flex">
                                <div class="px-4 py-1 text-gray-300" style="padding-left: {{ 16 + ($indent * 4) }}px;">
                                    {!! $highlighted !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Footer Status Bar -->
                <div class="bg-[#2d2e30] border-t border-gray-700 px-4 py-2 flex items-center justify-between text-xs text-gray-400">
                    <span>🌐 Document loaded</span>
                    <span>UTF-8 encoding</span>
                </div>
            </div>

            <!-- Hidden textarea for copying -->
            <textarea id="htmlContent" style="position: absolute; left: -9999px;">{{ $html }}</textarea>
        </div>

        <script>
            // Sync scrolling between line numbers and code
            const codeContainer = document.getElementById('codeContainer');
            const lineNumbers = document.getElementById('lineNumbers');
            
            if (codeContainer && lineNumbers) {
                codeContainer.addEventListener('scroll', () => {
                    lineNumbers.scrollTop = codeContainer.scrollTop;
                });
            }

            // Copy HTML function
            function copyHTML() {
                const textarea = document.getElementById('htmlContent');
                const btn = document.getElementById('copyBtn');
                
                textarea.select();
                document.execCommand('copy');
                
                btn.textContent = '✅ Copied!';
                btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                btn.classList.add('bg-green-600');
                
                setTimeout(() => {
                    btn.textContent = '📋 Copy';
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }, 2000);
            }
        </script>
    @endisset

</div>
</x-app-layout>