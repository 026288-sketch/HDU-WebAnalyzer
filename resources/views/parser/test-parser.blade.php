<x-app-layout title="Parser Testing">
    <x-slot name="header">
    </x-slot>
<div class="max-w-7xl mx-auto px-6 py-8 text-white" x-data>
    <h2 class="text-3xl font-bold mb-6">🧪 Link & Content Parser Testing</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- Left: Link Parser --}}
        <div class="bg-gray-800 p-6 rounded shadow">
            <h3 class="text-xl font-semibold mb-4">🔗 Link Parser Test</h3>

            <form method="POST" action="{{ route('parser.test.links') }}" class="mb-4">
                @csrf

                <label for="source_url" class="block mb-2 text-sm">Source URL:</label>
                <input type="text" id="source_url" name="source_url"
                       value="{{ old('source_url', $sourceUrl ?? '') }}" required
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400">

                <label for="regex" class="block mt-4 mb-2 text-sm">Regex pattern:</label>
                <input type="text" id="regex" name="regex"
                       value="{{ old('regex', $regex ?? '') }}" required
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400">

                <button type="submit"
                        class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    🔍 Start Parsing
                </button>
            </form>

            @isset($linkResults)
                <div class="mb-4">
                    <h4 class="text-lg font-semibold mb-2">Results:</h4>
                    <ul class="list-disc list-inside text-sm">
                        @forelse ($linkResults as $link)
                            <li>
                                <a href="{{ $link }}" class="text-blue-400 hover:underline" target="_blank">
                                    {{ $link }}
                                </a>
                            </li>
                        @empty
                            <li>No links found.</li>
                        @endforelse
                    </ul>
                </div>
            @endisset

            @isset($linkHtml)
                <div class="mt-4">
                    <h4 class="text-lg font-semibold mb-2">🔧 HTML Inspector</h4>
                    
                    <!-- Chrome DevTools-like Inspector -->
                    <div class="bg-[#202124] rounded-lg overflow-hidden shadow-2xl border border-gray-700">
                        
                        <!-- Header Toolbar -->
                        <div class="bg-[#2d2e30] border-b border-gray-700 px-4 py-2 flex items-center justify-between">
                            <div class="text-xs text-white font-semibold">
                                HTML Inspector
                            </div>
                            <div class="flex items-center space-x-3 text-xs text-gray-400">
                                <span>📄 {{ count(explode("\n", $linkHtml ?? '')) }} lines</span>
                                <span>💾 {{ round(strlen($linkHtml ?? '') / 1024, 2) }} KB</span>
                                <button onclick="copyHTML('linkHtmlContent', 'copyBtnLink')" id="copyBtnLink"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition text-xs font-semibold ml-2">
                                    📋 Copy
                                </button>
                            </div>
                        </div>

                        <!-- HTML Content with Line Numbers -->
                        <div class="flex bg-[#202124] overflow-hidden" style="height: 400px;">
                            <!-- Line Numbers -->
                            <div class="bg-[#2d2e30] text-gray-500 text-xs font-mono select-none border-r border-gray-700 overflow-y-auto" id="lineNumbersLink">
                                @php
                                    $htmlLines = explode("\n", $linkHtml ?? '');
                                @endphp
                                @foreach($htmlLines as $lineIndex => $line)
                                    <div class="px-3 py-1 text-right hover:bg-[#37373d] transition">{{ $lineIndex + 1 }}</div>
                                @endforeach
                            </div>

                            <!-- HTML Code with Syntax Highlighting -->
                            <div class="flex-1 overflow-auto font-mono text-sm" id="codeContainerLink">
                                @foreach($htmlLines as $lineIndex => $line)
                                    @php
                                        $escapedLine = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                        $indent = strlen($line) - strlen(ltrim($line));
                                        $trimmedLine = ltrim($line);
                                        $escapedTrimmed = htmlspecialchars($trimmedLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                        $highlighted = $escapedTrimmed;
                                        
                                        $highlighted = preg_replace('/(&lt;!--.*?--&gt;)/', '<span style="color:#6a9955;">$1</span>', $highlighted);
                                        $highlighted = preg_replace('/(&lt;!DOCTYPE[^&]*?&gt;)/', '<span style="color:#569cd6;">$1</span>', $highlighted);
                                        $highlighted = preg_replace_callback(
                                            '/&lt;(\/?)([a-zA-Z0-9\-:]+)(.*?)&gt;/',
                                            function($matches) {
                                                $openBracket = '&lt;';
                                                $slash = $matches[1];
                                                $tagName = $matches[2];
                                                $rest = $matches[3];
                                                $closeBracket = '&gt;';
                                                
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

                    <textarea id="linkHtmlContent" style="position: absolute; left: -9999px;">{{ $linkHtml }}</textarea>
                </div>
            @endisset
        </div>

        {{-- Right: Full Content Parser --}}
        <div class="bg-gray-800 p-6 rounded shadow">
            <h3 class="text-xl font-semibold mb-4">📄 Full Content Parser Test</h3>

            <form method="POST" action="{{ route('parser.test.content') }}" class="mb-4">
                @csrf
                <label for="article_url" class="block mb-2 text-sm">Article URL:</label>
                <input type="text" id="article_url" name="article_url"
                       value="{{ old('article_url') }}" required
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400">

                <div class="mt-2">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="use_browser" value="1"
                               class="form-checkbox h-5 w-5 text-green-600"
                               {{ old('use_browser') ? 'checked' : '' }}>
                        <span class="ml-2 text-sm">Use browser</span>
                    </label>
                </div>

                <button type="submit"
                        class="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    📥 Parse Content
                </button>
            </form>

            @isset($contentResults)
                <div class="mb-4">
                    <h4 class="text-lg font-semibold mb-2">Result:</h4>
                    <p><strong>Title:</strong> {{ $contentResults['title'] }}</p>
                    <p><strong>Excerpt:</strong> {{ $contentResults['summary'] ?? '—' }}</p>
                    <p><strong>Date:</strong> {{ $contentResults['timestamp'] }}</p>
                    @if (!empty($contentResults['image']))
                        <img src="{{ $contentResults['image'] }}" alt="Image" class="my-4 rounded">
                    @endif
                    <div class="prose prose-invert max-w-none mt-4">
                        {!! $contentResults['content'] !!}
                    </div>
                </div>

                <div class="mt-4">
                    <h4 class="text-lg font-semibold mb-2">🔧 HTML Inspector</h4>
                    
                    <!-- Chrome DevTools-like Inspector -->
                    <div class="bg-[#202124] rounded-lg overflow-hidden shadow-2xl border border-gray-700">
                        
                        <!-- Header Toolbar -->
                        <div class="bg-[#2d2e30] border-b border-gray-700 px-4 py-2 flex items-center justify-between">
                            <div class="text-xs text-white font-semibold">
                                HTML Inspector
                            </div>
                            <div class="flex items-center space-x-3 text-xs text-gray-400">
                                <span>📄 {{ count(explode("\n", $contentResults['html'] ?? '')) }} lines</span>
                                <span>💾 {{ round(strlen($contentResults['html'] ?? '') / 1024, 2) }} KB</span>
                                <button onclick="copyHTML('contentHtmlContent', 'copyBtnContent')" id="copyBtnContent"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition text-xs font-semibold ml-2">
                                    📋 Copy
                                </button>
                            </div>
                        </div>

                        <!-- HTML Content with Line Numbers -->
                        <div class="flex bg-[#202124] overflow-hidden" style="height: 400px;">
                            <!-- Line Numbers -->
                            <div class="bg-[#2d2e30] text-gray-500 text-xs font-mono select-none border-r border-gray-700 overflow-y-auto" id="lineNumbersContent">
                                @php
                                    $htmlLines = explode("\n", $contentResults['html'] ?? '');
                                @endphp
                                @foreach($htmlLines as $lineIndex => $line)
                                    <div class="px-3 py-1 text-right hover:bg-[#37373d] transition">{{ $lineIndex + 1 }}</div>
                                @endforeach
                            </div>

                            <!-- HTML Code with Syntax Highlighting -->
                            <div class="flex-1 overflow-auto font-mono text-sm" id="codeContainerContent">
                                @foreach($htmlLines as $lineIndex => $line)
                                    @php
                                        $escapedLine = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                        $indent = strlen($line) - strlen(ltrim($line));
                                        $trimmedLine = ltrim($line);
                                        $escapedTrimmed = htmlspecialchars($trimmedLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                        $highlighted = $escapedTrimmed;
                                        
                                        $highlighted = preg_replace('/(&lt;!--.*?--&gt;)/', '<span style="color:#6a9955;">$1</span>', $highlighted);
                                        $highlighted = preg_replace('/(&lt;!DOCTYPE[^&]*?&gt;)/', '<span style="color:#569cd6;">$1</span>', $highlighted);
                                        $highlighted = preg_replace_callback(
                                            '/&lt;(\/?)([a-zA-Z0-9\-:]+)(.*?)&gt;/',
                                            function($matches) {
                                                $openBracket = '&lt;';
                                                $slash = $matches[1];
                                                $tagName = $matches[2];
                                                $rest = $matches[3];
                                                $closeBracket = '&gt;';
                                                
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

                    <textarea id="contentHtmlContent" style="position: absolute; left: -9999px;">{{ $contentResults['html'] ?? '' }}</textarea>
                </div>
            @endisset
        </div>
    </div>
</div>

<script>
    // Sync scrolling for link parser
    const codeContainerLink = document.getElementById('codeContainerLink');
    const lineNumbersLink = document.getElementById('lineNumbersLink');
    
    if (codeContainerLink && lineNumbersLink) {
        codeContainerLink.addEventListener('scroll', () => {
            lineNumbersLink.scrollTop = codeContainerLink.scrollTop;
        });
    }

    // Sync scrolling for content parser
    const codeContainerContent = document.getElementById('codeContainerContent');
    const lineNumbersContent = document.getElementById('lineNumbersContent');
    
    if (codeContainerContent && lineNumbersContent) {
        codeContainerContent.addEventListener('scroll', () => {
            lineNumbersContent.scrollTop = codeContainerContent.scrollTop;
        });
    }

    // Copy HTML function
    function copyHTML(textareaId, btnId) {
        const textarea = document.getElementById(textareaId);
        const btn = document.getElementById(btnId);
        
        if (!textarea || !btn) return;
        
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
</x-app-layout>