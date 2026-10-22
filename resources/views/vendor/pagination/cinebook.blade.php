@php
    $btn = 'rounded-lg border border-[#283750] bg-[#0c1424] px-5 py-[13px] text-sm text-[#f4f7ff] whitespace-nowrap';
@endphp

<div class="flex items-center gap-4">
    <p class="flex-1 text-xs text-[#a0aec5]">
        Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
    </p>

    @if ($paginator->onFirstPage())
        <span class="{{ $btn }} cursor-not-allowed opacity-50">← Previous</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btn }} hover:border-[#347bff]">← Previous</a>
    @endif

    <div class="flex flex-1 items-center gap-3 text-xs">
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="text-[#a0aec5]">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="text-[#347bff]" aria-current="page">• {{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="text-[#a0aec5] hover:text-white">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btn }} hover:border-[#347bff]">Next →</a>
    @else
        <span class="{{ $btn }} cursor-not-allowed opacity-50">Next →</span>
    @endif
</div>