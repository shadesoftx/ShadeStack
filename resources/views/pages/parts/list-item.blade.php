@component('entities.list-item-basic', ['entity' => $page])
    <div class="entity-item-snippet">
        <p class="text-muted break-text text-limit-lines-2">{{ $page->getExcerpt() }}</p>
    </div>
@endcomponent
