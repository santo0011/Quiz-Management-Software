{{--
    Video tutorial cards (player, description, download link, clickable
    chapters). Shared by Super Admin → Video Tutorials and each panel's own
    tutorials page. Expects $videos (array from config/tutorials.php) and
    optional $emptyLabel.
--}}
@once
    <style>
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
    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-seek]');
            if (!button) return;
            const video = button.closest('.tutorial-card').querySelector('video');
            const [m, s] = button.dataset.seek.split(':').map(Number);
            video.currentTime = m * 60 + s;
            video.play();
            video.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    </script>
@endonce

@forelse ($videos as $video)
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
        <strong>{{ $emptyLabel ?? 'Videos are coming soon.' }}</strong>
    </div>
@endforelse
