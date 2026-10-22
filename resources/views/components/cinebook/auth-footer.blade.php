{{--
    Text block at the bottom of every account screen. Put your own lines
    (as paragraphs) in the slot; the standard CineBook line is added after them.
--}}
<div class="flex flex-col gap-[22px] text-[14px] leading-[1.6] text-cb-muted">
    {{ $slot }}

    <p>CineBook Downtown · Secure account access. Reservations use Stripe hosted Checkout in test mode.</p>
</div>
