<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Реєстрація - HDU WebAnalyzer</title>

    @vite(['resources/css/app.css', 'resources/js/auth.js'])
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-2 sm:p-4 relative overflow-hidden">

    <!-- Декоративні елементи -->
    <div class="absolute top-20 left-10 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse"></div>
    <div class="absolute bottom-20 right-10 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse" style="animation-delay: 1s;"></div>
    <div class="absolute top-1/2 left-1/2 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse" style="animation-delay: 2s;"></div>

    <div class="glass-effect shadow-2xl rounded-2xl p-3 sm:p-4 md:p-6 w-full max-w-md relative z-10 transform hover:scale-[1.02] transition-transform duration-300 my-2 sm:my-4">

        <!-- Лого/Іконка -->
        <div class="flex justify-center mb-2 sm:mb-3">
            <div class="relative">
                <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg flex items-center justify-center float">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-blue-400 rounded-full border-2 border-white"></div>
            </div>
        </div>

        <!-- Заголовок -->
        <div class="text-center mb-2 sm:mb-3">
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-1">
                Створити акаунт 🚀
            </h1>
            <p class="text-gray-600 text-xs">
                Приєднуйтесь до нашої системи аналізу
            </p>
        </div>

        <!-- Validation Errors -->
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded-lg mb-4 animate-shake">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-medium mb-1 text-sm">Помилка реєстрації:</p>
                        <ul class="list-disc list-inside space-y-0.5 text-xs">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Форма -->
        <form action="{{ route('register') }}" method="POST" class="space-y-3">
            @csrf

            <!-- Ім'я -->
            <div class="relative">
                <label for="name" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">
                    👤 Ім'я
                </label>
                <div class="relative">
                    <input
                        type="text"
                        name="name"
                        id="name"
                        required
                        autofocus
                        value="{{ old('name') }}"
                        placeholder="Іван Петренко"
                        class="input-focus w-full px-3 py-2 sm:py-2.5 pl-9 sm:pl-10 border-2 border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-300 @enderror"
                    >
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="relative">
                <label for="email" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">
                    📧 Email адреса
                </label>
                <div class="relative">
                    <input
                        type="email"
                        name="email"
                        id="email"
                        required
                        value="{{ old('email') }}"
                        placeholder="your@email.com"
                        autocomplete="username"
                        class="input-focus w-full px-3 py-2 sm:py-2.5 pl-9 sm:pl-10 border-2 border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('email') border-red-300 @enderror"
                    >
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                    </svg>
                </div>
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Пароль -->
            <div class="relative">
                <label for="password" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">
                    🔒 Пароль
                </label>
                <div class="relative">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        placeholder="Мінімум 8 символів"
                        autocomplete="new-password"
                        class="input-focus w-full px-3 py-2 sm:py-2.5 pl-9 sm:pl-10 pr-9 sm:pr-10 border-2 border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('password') border-red-300 @enderror"
                    >
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <button
                        type="button"
                        onclick="togglePassword()"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 focus:outline-none transition-colors"
                        tabindex="-1"
                    >
                        <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Підтвердження пароля -->
            <div class="relative">
                <label for="password_confirmation" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">
                    🔐 Підтвердження пароля
                </label>
                <div class="relative">
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        required
                        placeholder="Повторіть пароль"
                        autocomplete="new-password"
                        class="input-focus w-full px-3 py-2 sm:py-2.5 pl-9 sm:pl-10 pr-9 sm:pr-10 border-2 border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('password_confirmation') border-red-300 @enderror"
                    >
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <button
                        type="button"
                        onclick="togglePasswordConfirmation()"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 focus:outline-none transition-colors"
                        tabindex="-1"
                    >
                        <svg id="eye-icon-confirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
                @error('password_confirmation')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Кнопка реєстрації -->
            <button
                type="submit"
                class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-2.5 sm:py-3.5 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 font-semibold text-base sm:text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:translate-y-0 mt-2"
            >
                <span class="flex items-center justify-center">
                    Зареєструватися
                    <svg class="w-5 h-5 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </span>
            </button>
        </form>

        <!-- Вхід -->
        <div class="mt-4 text-center">
            <p class="text-gray-600 text-xs sm:text-sm">
                Вже маєте акаунт?
                <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold hover:underline ml-1 transition-colors">
                    Увійти →
                </a>
            </p>
        </div>

        <!-- Footer інфо -->
        <div class="mt-3 text-center">
            <p class="text-xs text-gray-500">
                🎓 HDU WebAnalyzer v1.0
            </p>
        </div>
    </div>

    <script>
        // Функція для показу/приховування підтвердження пароля
        function togglePasswordConfirmation() {
            const passwordInput = document.getElementById('password_confirmation');
            const eyeIcon = document.getElementById('eye-icon-confirm');

            if (!passwordInput || !eyeIcon) return;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        }

        // Робимо функцію глобальною
        window.togglePasswordConfirmation = togglePasswordConfirmation;
    </script>

</body>
</html>
