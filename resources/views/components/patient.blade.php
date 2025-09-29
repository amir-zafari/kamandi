<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
<div class="flex h-screen overflow-hidden">
    <div class="flex-1 flex flex-col overflow-auto">
        <main class="flex-1 bg-gray-100 p-6 overflow-auto">

            <div class="bg-white shadow rounded-lg">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>
</body>
</html>
