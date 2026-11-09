@if ($paginator->hasPages())
    <nav class="pagination">
        @if ($paginator->onFirstPage())
            <span class="disabled">‹ Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Previous</a>
        @endif

        <span class="muted">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }} · {{ $paginator->total() }} bookings</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
        @else
            <span class="disabled">Next ›</span>
        @endif
    </nav>
@endif
