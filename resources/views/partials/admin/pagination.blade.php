@if ($paginator->hasPages())
  <nav class="admin-pagination" role="navigation" aria-label="Pagination">
    <p class="admin-pagination__meta">
      Showing
      <strong>{{ $paginator->firstItem() }}</strong>
      &ndash;
      <strong>{{ $paginator->lastItem() }}</strong>
      of
      <strong>{{ $paginator->total() }}</strong>
    </p>
    <div class="admin-pagination__links">
      {{ $paginator->onEachSide(1)->links('pagination.admin') }}
    </div>
  </nav>
@endif
