@php
    $nav = [
        'today'  => ['Today', route('ui.staff.today')],
        'scan'   => ['Scan', route('ui.staff.scan')],
        'roster' => ['Roster', route('ui.staff.roster')],
        'lookup' => ['Reference lookup', route('ui.staff.lookup')],
    ];
    $active = $active ?? '';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Staff · CineBook' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#080e1b] font-['Inter',sans-serif] text-[#a0aec5] antialiased">
    <header class="flex flex-wrap items-center justify-between gap-x-8 gap-y-3 border-b border-[#283750] px-4 py-4 sm:px-8 lg:px-16">
        <div class="flex items-center gap-3">
            <a href="{{ $nav['today'][1] }}" class="text-[26px] leading-none text-white"><span class="text-[#347bff]">▰</span> cinebook</a>
            <span class="text-[10px] font-semibold uppercase tracking-wide">• Staff</span>
        </div>

        <nav class="order-3 flex w-full justify-center gap-6 text-sm md:order-none md:w-auto">
            @foreach ($nav as $key => [$label, $href])
                <a href="{{ $href }}" class="{{ $active === $key ? 'text-[#87beff]' : 'hover:text-white' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="text-xs">
            {{ auth()->user()->name ?? 'Sam Rivera' }} ·
            @if (Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}" class="inline">@csrf
                    <button type="submit" class="hover:text-white">Sign out</button>
                </form>
            @else
                Sign out
            @endif
        </div>
    </header>

   <main class="px-4 py-8 sm:px-8 lg:px-16 lg:py-10">
    {{ $slot ?? '' }}
    @yield('content')
</main>
</body>
</html>