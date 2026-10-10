@if ($paginator->hasPages())
<nav class="pager" role="navigation" aria-label="Pagination">
    @if ($paginator->onFirstPage())
        <span class="btn btn--ghost is-disabled">‹ Newer</span>
    @else
        <a class="btn btn--ghost" href="{{ $paginator->previousPageUrl() }}">‹ Newer</a>
    @endif
    <span class="muted small">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
    @if ($paginator->hasMorePages())
        <a class="btn btn--ghost" href="{{ $paginator->nextPageUrl() }}">Older ›</a>
    @else
        <span class="btn btn--ghost is-disabled">Older ›</span>
    @endif
</nav>
@endif
