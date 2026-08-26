@php($sessions = $academicSessions ?? collect())
@php($current = $selectedAcademicSession ?? null)

<div class="dropdown session-selector">
    <button class="current-branch-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Academic session scope">
        <i class="bi bi-calendar-range"></i>
        <span>{{ $current?->name ?? 'Select Session' }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end session-dropdown-menu">
        @forelse ($sessions as $session)
            <li>
                <form method="POST" action="{{ route($prefix.'.academic-session-selection.store') }}" class="session-dropdown-form">
                    @csrf
                    <input type="hidden" name="academic_session_id" value="{{ $session->id }}">
                    <button type="submit" class="dropdown-item{{ $current?->id === $session->id ? ' active' : '' }}">
                        <span>{{ $session->name }}</span>
                        @unless ($session->is_active)
                            <span class="badge text-bg-light border ms-2">Closed</span>
                        @endunless
                    </button>
                </form>
            </li>
        @empty
            <li><span class="dropdown-item-text text-muted">No sessions yet</span></li>
        @endforelse
        @if ($prefix === 'admin')
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('admin.academic-sessions.index') }}"><i class="bi bi-gear-fill me-1"></i> Manage Sessions</a></li>
        @endif
    </ul>
</div>
