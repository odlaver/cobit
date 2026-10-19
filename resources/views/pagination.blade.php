@if ($paginator->hasPages())
    @php
        $link = 'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border px-3 text-sm font-semibold transition-colors';
        $idle = 'border-navy-100 bg-white text-navy-800 hover:bg-navy-50';
        $off = 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400';
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-navy-600">
            Halaman <span class="font-semibold text-navy-900">{{ $paginator->currentPage() }}</span> dari {{ $paginator->lastPage() }}
        </p>

        <div class="flex flex-wrap items-center gap-2">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="{{ $link }} {{ $off }}">{{ __('pagination.previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }} {{ $idle }}">{{ __('pagination.previous') }}</a>
            @endif

            {{-- Nomor halaman mulai layar sedang --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-hidden="true" class="hidden px-1 text-navy-500 sm:inline">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $link }} hidden border-navy-900 bg-navy-900 text-white sm:inline-flex">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Halaman {{ $page }}" class="{{ $link }} {{ $idle }} hidden sm:inline-flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }} {{ $idle }}">{{ __('pagination.next') }}</a>
            @else
                <span aria-disabled="true" class="{{ $link }} {{ $off }}">{{ __('pagination.next') }}</span>
            @endif
        </div>
    </nav>
@endif
