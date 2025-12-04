<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Відновлення пароля - HDU WebAnalyzer</title>

    @vite(['resources/css/app.css', 'resources/js/auth.js'])
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-2 sm:p-4 relative overflow-hidden">

    <!-- Декоративні елементи -->
    <div class="absolute top-20 left-10 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse"></div>
    <div class="absolute bottom-20 right-10 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse" style="animation-delay: 1s;"></div>
    <div class="absolute top-1/2 left-1/2 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse" style="animation-delay: 2s;"></div>

    <div class="glass-effect shadow-2xl rounded-2xl p-4 sm:p-6 md:p-8 w-full max-w-md relative z-10 transform hover:scale-[1.02] transition-transform duration-300 my-4">

        <!-- Лого/Іконка -->
        <div class="flex justify-center mb-4">
            <div class="relative">
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg flex items-center justify-center float">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-amber-400 rounded-full border-4 border-white"></div>
            </div>
        </div>

        <!-- Заголовок -->
        <div class="text-center mb-4 sm:mb-6">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
                Забули пароль? 🔑
            </h1>
            <p class="text-gray-600 text-xs sm:text-sm leading-relaxed">
                Не хвилюйтесь! Вкажіть вашу email адресу, і ми надішлемо посилання для скидання пароля
            </p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium text-sm">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <!-- Validation Errors -->
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 animate-shake">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-medium mb-2">Помилка:</p>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Форма -->
        <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Email -->
            <div class="relative">
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                    📧 Email адреса
                </label>
                <div class="relative">
                    <input
                        type="email"
                        name="email"
                        id="email"
                        required
                        autofocus
                        value="{{ old('email') }}"
                        placeholder="admin@example.com"
                        class="input-focus w-full px-3 py-2.5 sm:px-4 sm:py-3 pl-10 sm:pl-12 border-2 border-gray-200 rounded-xl text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('email') border-red-300 @enderror"
                    >
                    <svg class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                    </svg>
                </div>
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Кнопка надсилання -->
            <button
                type="submit"
                class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-2.5 sm:py-3.5 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 font-semibold text-base sm:text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:translate-y-0"
            >
                <span class="flex items-center justify-center">
                    Надіслати посилання
                    <svg class="w-5 h-5 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </span>
            </button>
        </form>

        <!-- Повернутись до входу -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-center text-gray-600 text-sm">
                Згадали пароль?
                <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold hover:underline ml-1 transition-colors">
                    Увійти →
                </a>
            </p>
        </div>

        <!-- Footer інфо -->
        <div class="mt-6 text-center">
            <p class="text-xs text-gray-500">
                🎓 HDU WebAnalyzer v1.0
            </p>
        </div>
    </div>

</body>
</html>
