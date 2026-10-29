@php
    $qr = function (string $seed) {
        $s = crc32($seed); $out = '';
        foreach ([[0,0],[18,0],[0,18]] as [$x,$y]) {
            $out .= "<rect x='$x' y='$y' width='7' height='7' fill='#000'/><rect x='".($x+1)."' y='".($y+1)."' width='5' height='5' fill='#fff'/><rect x='".($x+2)."' y='".($y+2)."' width='3' height='3' fill='#000'/>";
        }
        for ($i = 0; $i < 70; $i++) {
            $px = (($i * 7 + $s) % 17) + 8; $py = (($i * 11 + $s) % 25);
            $out .= "<rect x='$px' y='$py' width='1' height='1' fill='#000'/>";
        }
        return "<svg viewBox='0 0 25 25' width='52' height='52' shape-rendering='crispEdges'>$out</svg>";
    };

    $sign = ['See you at the cinema,', 'The CineBook Downtown team'];

    $emails = [
        [
            'subject' => 'Verify your CineBook email',
            'pre'     => 'One quick step before you reserve seats.',
            'title'   => 'Make your email official.',
            'body'    => ['Verify alex.morgan@example.com to hold seats and check out. Public browsing is always available.'],
            'button'  => 'Verify email address →',
            'after'   => ['If you didn’t create a CineBook account, you can ignore this message. Need a new link? Return to the verification page.'],
        ],
        [
            'subject' => 'Reset your CineBook password',
            'pre'     => 'A secure link is ready for you.',
            'title'   => 'A fresh start, safely.',
            'body'    => ['We received a request to reset your password. Use the secure link below to choose a new one.'],
            'button'  => 'Reset password →',
            'after'   => ['If you didn’t ask for this, ignore this email. Your password and existing bookings won’t change. A reset link can be replaced from Forgot password.'],
        ],
        [
            'subject' => 'Confirmed: The Last Horizon · 2 tickets',
            'pre'     => 'G7 and G8 are ready for your movie night.',
            'title'   => 'You’re going to the movies.',
            'body'    => ['Your payment is verified and booking CB-261015-0482 is confirmed.', 'The Last Horizon · Thu, Oct 15, 2026 · 19:30 · CineBook Downtown · Screen 1 · G7 + G8 · Premium · $24.00 USD'],
            'tickets' => [['G7', 'T-0482-01'], ['G8', 'T-0482-02']],
            'after'   => ['Entry 18:30–20:00. Each QR works once. Cancel the full booking until 17:30 only if no ticket has been scanned.'],
            'button'  => 'View tickets · Open booking details →',
        ],
        [
            'subject' => 'Your CineBook payment failed',
            'pre'     => 'No booking was confirmed. Your seats were released.',
            'title'   => 'Payment didn’t go through.',
            'body'    => ['Stripe reported a failed payment for The Last Horizon · Oct 15 · 19:30. Booking CB-261015-0482 is not confirmed.', 'G7 and G8 have been released. No valid tickets were issued.'],
            'button'  => 'Choose seats again →',
            'after'   => [],
        ],
        [
            'subject' => 'Booking cancelled · $24.00 refund requested',
            'pre'     => 'Your tickets are void. Refund processing is pending.',
            'title'   => 'Cancelled, with clarity.',
            'body'    => ['We cancelled CB-261015-0482 on your request on Oct 15 at 17:00. The Last Horizon · 19:30 · Screen 1.', 'G7 and G8 released. Both tickets void. Full refund of $24.00 USD requested.', 'The refund is not completed yet. We’ll update your booking when Stripe confirms it. If it fails, the cinema team will contact you. Tickets remain void.'],
            'button'  => 'View cancellation details →',
            'after'   => [],
        ],
        [
            'subject' => 'Showtime cancelled: The Last Horizon',
            'pre'     => 'We’re sorry. Your full refund has been requested.',
            'title'   => 'A change to your movie night.',
            'body'    => ['We’re sorry — The Last Horizon on Oct 15 at 19:30 has been cancelled by the cinema. Your booking was cancelled automatically.', 'You don’t need to request the refund. Your full refund has been issued/requested of $24.00 USD. Booking CB-261015-0482. Completion will be shown in My bookings.'],
            'button'  => 'View cancellation details →',
            'after'   => [],
        ],
        [
            'subject' => 'Your payment arrived too late',
            'pre'     => 'Your seats were released first, so we refunded it.',
            'title'   => 'Your payment arrived too late.',
            'body'    => ['Your payment for CB-261015-0482 arrived after your seats were released, so the booking could not be confirmed.', 'We’ve started a full refund of $24.00 USD. You can choose seats again if they are still available.'],
            'button'  => 'View refund status →',
            'after'   => [],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transactional emails · CineBook</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#080e1b] font-['Inter',sans-serif] text-[#a0aec5] antialiased">
    <div class="mx-auto max-w-[1440px] px-4 py-8 sm:px-8 lg:px-16 lg:py-10">
        <p class="text-xl text-white"><span class="text-[#347bff]">▰</span> cinebook</p>
        <p class="mt-6 text-[10px] uppercase tracking-wide">Cinebook · Companion states</p>
        <h1 class="mt-2 text-3xl font-bold text-[#f4f7ff] sm:text-[38px] sm:leading-[1.2]">Seven emails. One dependable source of truth.</h1>

        <div class="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($emails as $e)
                <div class="flex flex-col gap-3">
                    {{-- Header strip --}}
                    <div class="rounded-md bg-[#e9edf5] p-3 text-[11px] leading-[1.6] text-[#4b5563]">
                        <p>TO: Alex Morgan &lt;alex.morgan@example.com&gt;</p>
                        <p>SUBJECT: {{ $e['subject'] }}</p>
                        <p>{{ $e['pre'] }}</p>
                    </div>

                    {{-- Email --}}
                    <div class="overflow-hidden rounded-md bg-white">
                        <div class="bg-[#080e1b] px-6 py-3 text-lg text-white"><span class="text-[#347bff]">▰</span> cinebook</div>
                        <div class="flex flex-col gap-4 px-6 py-6 text-[13px] leading-[1.6] text-[#4b5563]">
                            <h2 class="text-2xl font-bold leading-[1.2] text-[#0f1a2e]">{{ $e['title'] }}</h2>
                            <p>Hi Alex,</p>
                            @foreach ($e['body'] as $p)
                                <p>{{ $p }}</p>
                            @endforeach

                            @foreach ($e['tickets'] ?? [] as [$seat, $code])
                                <div class="flex items-center gap-4 rounded-md bg-[#eef1f6] p-3">
                                    <div class="shrink-0 rounded bg-white p-1">{!! $qr($code) !!}</div>
                                    <div class="text-[12px] leading-[1.5]">
                                        <p class="font-bold text-[#0f1a2e]">{{ $seat }} · Admit one</p>
                                        <p>The Last Horizon · Screen 1 · Oct 15 · 19:30</p>
                                        <p>CB-261015-0482 · {{ $code }}</p>
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($e['after'] as $p)
                                <p>{{ $p }}</p>
                            @endforeach

                            <div><a href="#" class="inline-block rounded-lg bg-[#347bff] px-5 py-3 text-sm font-semibold text-white">{{ $e['button'] }}</a></div>

                            <p>{{ $sign[0] }}<br>{{ $sign[1] }}</p>
                            <p class="border-t border-[#e5e7eb] pt-3 text-[10px] leading-[1.5] text-[#9ca3af]">CineBook Downtown · 120 Market Street, Downtown. This is a transactional message about your account or reservation. Stripe TEST MODE · USD · No real money.</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>