@extends('layouts.tri')

@php
    $isHighLevelHomepage = str_contains($customHomepage->html ?? '', 'sotx-home');
@endphp

@if($isHighLevelHomepage)
    @push('body-class', 'sotx-homepage ')
@endif

@section('body')
    <div class="{{ $isHighLevelHomepage ? '' : 'mt-m' }}">
        <main class="content-wrap card">
            <div component="page-display"
                 option:page-display:page-id="{{ $customHomepage->id }}"
                 class="page-content">
                @include('pages.parts.page-display', ['page' => $customHomepage])
            </div>
        </main>
    </div>
@stop

@section('left')
    @if(!$isHighLevelHomepage)
        @include('home.parts.sidebar')
    @endif
@stop

@section('right')
    @if(!$isHighLevelHomepage)
        <div class="actions mb-xl">
            <h5>{{ trans('common.actions') }}</h5>
            <div class="icon-list text-link">
                @include('home.parts.expand-toggle', ['classes' => 'text-link', 'target' => '.entity-list.compact .entity-item-snippet', 'key' => 'home-details'])
                @include('common.dark-mode-toggle', ['classes' => 'icon-list-item text-link'])
            </div>
        </div>
    @endif
@stop
