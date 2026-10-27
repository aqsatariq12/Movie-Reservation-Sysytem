@props([
    'code',
    'title',
    'message',
    'icon' => 'film',
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ $title }} - CineBook</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-cb-cream font-cinebook text-cb-ink antialiased">
    <div class="mx-auto flex min-h-screen w-full max-w-3xl flex-col gap-8 px-6 py-10">
        <a href="{{ route('home') }}" class="text-[26px] leading-none">▰ cinebook</a>

        <main class="flex flex-1 items-center">
            <section class="flex w-full flex-col items-start gap-7 rounded-3xl border border-[#e3d8c8] bg-white p-8 sm:p-12">

                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"
                     stroke-linejoin="round" class="shrink-0 text-[#347bff]" aria-hidden="true">
                    @switch($icon)
                        @case('circle-x')
                            <circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>
                            @break
                        @case('clock-alert')
                            <path d="M12 6v6l4 2"/><path d="M16 21.16a10 10 0 1 1 5-13.516"/><path d="M20 11.5v6"/><path d="M20 21.5h.01"/>
                            @break
                        @case('hourglass')
                            <path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/>
                            @break
                        @case('circle-alert')
                            <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>
                            @break
                        @default
                            <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 3v18"/><path d="M3 7.5h4"/><path d="M3 12h18"/><path d="M3 16.5h4"/><path d="M17 3v18"/><path d="M17 7.5h4"/><path d="M17 16.5h4"/>
                    @endswitch
                </svg>

                <div class="flex flex-col gap-4">
                    <p class="text-7xl leading-[1.2] text-[#6b7280]">{{ $code }}</p>
                    <h1 class="text-3xl font-bold leading-[1.2] sm:text-4xl">{{ $title }}</h1>
                    <p class="max-w-xl text-sm leading-relaxed text-[#6b7280]">{{ $message }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    {{ $slot }}
                </div>

                <p class="text-xs text-[#6b7280]">CineBook Downtown · No technical details are shown.</p>
            </section>
        </main>
    </div>
</body>
</html>