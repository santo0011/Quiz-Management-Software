@if (($siblingStudents ?? collect())->count() > 1)
    <div class="guardian-switcher" role="tablist" aria-label="Switch student">
        <span class="guardian-switcher-label"><i class="bi bi-arrow-left-right"></i> Switch Student</span>
        <div class="guardian-switcher-list">
            @foreach ($siblingStudents as $siblingStudent)
                <a
                    href="{{ route('guardian.students.show', $siblingStudent) }}"
                    class="guardian-switcher-pill {{ $siblingStudent->id === $student->id ? 'active' : '' }}"
                    role="tab"
                    aria-selected="{{ $siblingStudent->id === $student->id ? 'true' : 'false' }}"
                >
                    <span class="guardian-switcher-avatar">{{ strtoupper(substr($siblingStudent->student_name, 0, 1)) }}</span>
                    <span>
                        <strong>{{ $siblingStudent->student_name }}</strong>
                        <small>{{ $siblingStudent->schoolClass?->name ?? $siblingStudent->class }}</small>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif
