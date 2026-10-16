{{-- Blue form submit button (Figma "Action" on the account screens). --}}
<button
    type="submit"
    {{ $attributes->class('inline-flex cursor-pointer items-center self-start whitespace-nowrap rounded-xl border border-cb-brand bg-cb-brand px-5 py-[13px] text-[14px] leading-[normal] text-white shadow-[0_10px_24px_rgba(52,123,255,0.15)] transition-colors hover:bg-cb-brand/90') }}
>{{ $slot }}</button>
