<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід у систему</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="bg-white shadow-xl rounded-xl p-10 w-full max-w-md text-center">
        <h1 class="text-3xl font-extrabold text-gray-800 mb-8">Вхід у систему</h1>

        @if(session('error'))
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 font-medium">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-5 text-left">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email" required
                       class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       value="{{ old('email') }}">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                <input type="password" name="password" id="password" required
                       class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 text-white py-2.5 rounded-lg hover:bg-indigo-700 transition-colors font-semibold">
                Увійти
            </button>
        </form>

        <p class="mt-6 text-gray-600 text-sm">
            Ще не маєте акаунта?
            <a href="{{ route('register') }}" class="text-indigo-600 hover:underline font-medium">Зареєструватися</a>
        </p>
    </div>

</body>
</html>
