{{-- White info card (Figma "Notice"). Used for session messages and hints. --}}
<div {{ $attributes->class('rounded-2xl border border-cb-line bg-white p-4 text-[14px] leading-[1.6] text-cb-ink shadow-[0_8px_24px_rgba(0,0,0,0.03)]') }}>
    {{ $slot }}
</div>
