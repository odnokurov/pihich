@if ($paginator->hasPages())
    <nav class="filter pagination" aria-label="Страницы списка">
        @if ($paginator->previousPageUrl())
            <a href="{{ $paginator->previousPageUrl() }}">Назад</a>
        @endif
        <span>{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->nextPageUrl())
            <a href="{{ $paginator->nextPageUrl() }}">Далее</a>
        @endif
    </nav>
@endif
