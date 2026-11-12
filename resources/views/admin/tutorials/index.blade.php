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
        .tutorial-card { display:grid; grid-template-columns:minmax(0,1.7fr) minmax(260px,1fr); gap:1.25rem; background:#fff; border:1px solid #e5e7eb; border-radius:1rem; padding:1rem; margin-bottom:1.25rem; }
        .tutorial-card video { width:100%; border-radius:.75rem; background:#0f172a; aspect-ratio:1366/752; display:block; }
        .tutorial-card h3 { font-size:1.15rem; font-weight:700; margin:0 0 .35rem; }
        .tutorial-card .meta { color:#6b7280; font-size:.85rem; margin-bottom:.6rem; display:flex; gap:.75rem; flex-wrap:wrap; }
        .tutorial-card p { color:#4b5563; font-size:.92rem; }
        .chapter-list { list-style:none; padding:0; margin:0; max-height:330px; overflow:auto; }
        .chapter-list button { width:100%; display:flex; gap:.6rem; align-items:flex-start; text-align:left; border:0; background:transparent; padding:.4rem .5rem; border-radius:.5rem; color:#374151; font-size:.88rem; }
        .chapter-list button:hover { background:#eef2ff; color:#4f46e5; }
        .chapter-list .time { font-variant-numeric:tabular-nums; font-weight:700; color:#4f46e5; min-width:3rem; }
        .tutorial-empty { text-align:center; padding:2.5rem 1rem; background:#fff; border:1px dashed #d1d5db; border-radius:1rem; color:#6b7280; }
        .tutorial-empty i { font-size:2.25rem; display:block; margin-bottom:.5rem; color:#9ca3af; }
        @media (max-width: 992px) { .tutorial-card { grid-template-columns:1fr; } }
    </style>

    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>Video Tutorials</h2>
                <p>Short narrated videos that walk through each panel step by step. Click a chapter to jump to that part.</p>
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
                @forelse ($panel['videos'] as $video)
                    <article class="tutorial-card">
                        <div>
                            <video controls preload="metadata" playsinline
                                   @if (! empty($video['poster'])) poster="{{ asset($video['poster']) }}" @endif>
                                <source src="{{ asset($video['file']) }}" type="video/mp4">
                                Your browser can't play this video. <a href="{{ asset($video['file']) }}">Download it</a>.
                            </video>
                        </div>
                        <div>
                            <h3>{{ $video['title'] }}</h3>
                            <div class="meta">
                                <span><i class="bi bi-clock"></i> {{ $video['duration'] ?? '' }}</span>
                                <a href="{{ asset($video['file']) }}" download><i class="bi bi-download"></i> Download</a>
                            </div>
                            <p>{{ $video['description'] }}</p>
                            @if (! empty($video['chapters']))
                                <h6 class="text-uppercase text-muted small fw-bold mb-2">Chapters</h6>
                                <ul class="chapter-list">
                                    @foreach ($video['chapters'] as [$time, $label])
                                        <li>
                                            <button type="button" data-seek="{{ $time }}">
                                                <span class="time">{{ $time }}</span>
                                                <span>{{ $label }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="tutorial-empty">
                        <i class="bi bi-camera-video"></i>
                        <strong>{{ $panel['label'] }} videos are coming soon.</strong>
                    </div>
                @endforelse
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

        document.querySelectorAll('[data-seek]').forEach((button) => {
            button.addEventListener('click', () => {
                const video = button.closest('.tutorial-card').querySelector('video');
                const [m, s] = button.dataset.seek.split(':').map(Number);
                video.currentTime = m * 60 + s;
                video.play();
                video.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
    </script>
@endsection
