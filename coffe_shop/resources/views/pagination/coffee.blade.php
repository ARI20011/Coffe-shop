@if ($paginator->hasPages())
    <nav class="coffee-pagination" role="navigation" aria-label="Navigasi halaman">
        <span class="coffee-pagination__summary">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
        </span>
        <div class="coffee-pagination__links">
            @if ($paginator->onFirstPage())
                <span class="is-disabled" aria-disabled="true">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="is-disabled" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
            @else
                <span class="is-disabled" aria-disabled="true">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif
