@props(['title' => 'CineBook Downtown'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cb-cream font-cinebook text-cb-ink antialiased">
    <x-cinebook.navbar />

    <main>
        {{ $slot }}
    </main>

    <x-cinebook.footer />
</body>
</html>
