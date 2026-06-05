@component('entities.list-item-basic', ['entity' => $entity, 'classes' => (($locked ?? false) ? 'disabled ' : '') . ($classes ?? '') ])

<div class="entity-item-snippet">
    @php
        $previewText = $entity->preview_content ?? $entity->getExcerpt();
        $isSearchResult = str_contains($classes ?? '', 'search-result-item');

        if ($isSearchResult) {
            $previewText = strip_tags($previewText);
            $titleLead = trim(explode('-', $entity->name, 2)[0]);
            if (strlen($titleLead) > 3) {
                $previewText = trim(preg_replace('/^(' . preg_quote($titleLead, '/') . '\\s*)+/i', '', $previewText));
            }
            if ($entity->relationLoaded('chapter') && $entity->chapter && strlen($entity->chapter->name) > 3) {
                $previewText = trim(preg_replace('/^(' . preg_quote($entity->chapter->name, '/') . '\\s*)+/i', '', $previewText));
            }
            $previewText = trim(preg_replace('/^(' . preg_quote($entity->name, '/') . '\\s*)+/i', '', $previewText));
            $previewText = Str::limit($previewText, 145);
        }
    @endphp

    @if($locked ?? false)
        <div class="text-warn my-xxs bold">
            @icon('lock'){{ trans('entities.entity_select_lack_permission') }}
        </div>
    @endif

    @if($showPath ?? false)
        @if($entity->relationLoaded('book') && $entity->book)
            <span class="text-book">{{ $entity->book->getShortName(42) }}</span>
            @if($entity->relationLoaded('chapter') && $entity->chapter)
                <span class="text-muted entity-list-item-path-sep">@icon('chevron-right')</span> <span class="text-chapter">{{ $entity->chapter->getShortName(42) }}</span>
            @endif
        @endif
    @endif

    <p class="text-muted break-text">{{ $previewText }}</p>
</div>

@if(($showTags ?? false) && $entity->tags->count() > 0)
    <div class="entity-item-tags mt-xs">
        @include('entities.tag-list', ['entity' => $entity, 'linked' => false ])
    </div>
@endif

@if(($showUpdatedBy ?? false) && $entity->relationLoaded('updatedBy') && $entity->updatedBy)
    <small title="{{ $dates->absolute($entity->updated_at) }}">
        {!! trans('entities.meta_updated_name', [
            'timeLength' => $dates->relative($entity->updated_at),
            'user' => e($entity->updatedBy->name)
        ]) !!}
    </small>
@endif

@endcomponent
