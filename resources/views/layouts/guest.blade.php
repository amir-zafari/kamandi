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
    <body class="flex justify-between items-center font-roboto text-gray-900 ">
    <section class="flex flex-col w-4/12 min-h-screen justify-center items-center">
        <div>
            <a href="/" wire:navigate>
                <x-application-logo/>
            </a>
        </div>
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </section>
    <section
        class="w-8/12 min-h-screen bg-center bg-cover bg-no-repeat"
        style="background-image: url('{{ asset('images/guest-bg.jpg') }}')">
    </section>

    </body>
</html>
