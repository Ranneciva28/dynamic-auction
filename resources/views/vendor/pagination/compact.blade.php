@if ($paginator->hasPages())
<nav class="pager" role="navigation" aria-label="Navigasi halaman">
  @if ($paginator->onFirstPage())<span class="pager-link disabled" aria-disabled="true">‹ Sebelumnya</span>
  @else<a class="pager-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Sebelumnya</a>@endif
  @foreach ($elements as $element)
    @if (is_string($element))<span class="pager-ellipsis" aria-hidden="true">{{ $element }}</span>@endif
    @if (is_array($element))
      @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())<span class="pager-link active" aria-current="page">{{ $page }}</span>
        @else<a class="pager-link" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>@endif
      @endforeach
    @endif
  @endforeach
  @if ($paginator->hasMorePages())<a class="pager-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya ›</a>
  @else<span class="pager-link disabled" aria-disabled="true">Berikutnya ›</span>@endif
</nav>
@endif
