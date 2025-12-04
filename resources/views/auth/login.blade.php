<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Вхід у систему - HDU WebAnalyzer</title>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-400 rounded-full border-4 border-white"></div>
            </div>
        </div>

        <!-- Заголовок -->
        <div class="text-center mb-4 sm:mb-6">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
                Вітаємо знову! 👋
            </h1>
            <p class="text-gray-600 text-xs sm:text-sm">
                Увійдіть, щоб продовжити роботу з системою
            </p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
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
                        <p class="font-medium mb-2">Помилка входу:</p>
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
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
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

            <!-- Пароль -->
            <div class="relative">
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                    🔒 Пароль
                </label>
                <div class="relative">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        placeholder="••••••••"
                        class="input-focus w-full px-3 py-2.5 sm:px-4 sm:py-3 pl-10 sm:pl-12 pr-10 sm:pr-12 border-2 border-gray-200 rounded-xl text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('password') border-red-300 @enderror"
                    >
                    <svg class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <button
                        type="button"
                        onclick="togglePassword()"
                        class="absolute right-4 top-3.5 text-gray-400 hover:text-gray-600 focus:outline-none transition-colors"
                        tabindex="-1"
                    >
                        <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Запам'ятати мене + Забув пароль -->
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center cursor-pointer group">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer transition-colors"
                        {{ old('remember') ? 'checked' : '' }}
                    >
                    <span class="ml-2 text-gray-600 group-hover:text-gray-800 transition-colors">Запам'ятати мене</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-indigo-600 hover:text-indigo-800 font-medium hover:underline transition-colors">
                        Забули пароль?
                    </a>
                @endif
            </div>

            <!-- Кнопка входу -->
            <button
                type="submit"
                class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-2.5 sm:py-3.5 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 font-semibold text-base sm:text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:translate-y-0"
            >
                <span class="flex items-center justify-center">
                    Увійти в систему
                    <svg class="w-5 h-5 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </span>
            </button>
        </form>

        <!-- Реєстрація -->
        @if (Route::has('register'))
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-center text-gray-600">
                    Ще не маєте акаунта?
                    <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold hover:underline ml-1 transition-colors">
                        Зареєструватися →
                    </a>
                </p>
            </div>
        @endif

        <!-- Footer інфо -->
        <div class="mt-6 text-center">
            <p class="text-xs text-gray-500">
                🎓 HDU WebAnalyzer v1.0
            </p>
        </div>
    </div>

</body>
</html>
