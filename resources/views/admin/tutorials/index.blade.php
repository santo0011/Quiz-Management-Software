@extends('layouts.admin')

@section('title', 'Video Tutorials')
@section('page-title', 'Video Tutorials')

@php
    $panels = config('tutorials.panels', []);
@endphp

@section('content')
    <style>
        .tutorial-tabs { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.25rem; }
        .tutorial-tab { display:inline-flex; align-items:center; gap:.45rem; border:1px solid #e5e7eb; background:#fff; color:#374151; border-radius:999px; padding:.45rem .95rem; font-weight:600; font-size:.9rem; cursor:pointer; }
        .tutorial-tab .count { background:#f3f4f6; border-radius:999px; padding:0 .5rem; font-size:.75rem; color:#6b7280; }
        .tutorial-tab.is-active { background:var(--tab-color); border-color:var(--tab-color); color:#fff; }
        .tutorial-tab.is-active .count { background:rgba(255,255,255,.25); color:#fff; }
    </style>

    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>Video Tutorials</h2>
                <p>Short narrated videos that walk through each panel step by step. Click a chapter to jump to that part.
                    Each panel also sees its own videos under Video Tutorials in its sidebar.</p>
            </div>
        </div>

        <div class="tutorial-tabs" role="tablist">
            @foreach ($panels as $key => $panel)
                <button type="button" class="tutorial-tab{{ $loop->first ? ' is-active' : '' }}" style="--tab-color: {{ $panel['color'] }}"
                        data-tutorial-tab="{{ $key }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    <i class="bi {{ $panel['icon'] }}"></i>
                    {{ $panel['label'] }}
                    <span class="count">{{ count($panel['videos']) }}</span>
                </button>
            @endforeach
        </div>

        @foreach ($panels as $key => $panel)
            <div data-tutorial-panel="{{ $key }}" @unless ($loop->first) hidden @endunless>
                @include('tutorials.partials.videos', [
                    'videos' => $panel['videos'],
                    'emptyLabel' => $panel['label'].' videos are coming soon.',
                ])
            </div>
        @endforeach
    </section>

    <script>
        document.querySelectorAll('[data-tutorial-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('[data-tutorial-tab]').forEach((t) => {
                    const active = t === tab;
                    t.classList.toggle('is-active', active);
                    t.setAttribute('aria-selected', String(active));
                });
                document.querySelectorAll('[data-tutorial-panel]').forEach((panel) => {
                    panel.hidden = panel.dataset.tutorialPanel !== tab.dataset.tutorialTab;
                });
                document.querySelectorAll('video').forEach((v) => v.pause());
            });
        });
    </script>
@endsection
