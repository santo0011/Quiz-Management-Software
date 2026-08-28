@extends('layouts.guardian')

@section('title', 'My Students')
@section('page-title', 'My Students')

@section('content')
    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>Guardian Dashboard</span>
                <h2>My Students</h2>
            </div>
            <span class="status-badge status-published">
                <i class="bi bi-people-fill"></i>
                {{ $students->count() }} {{ Str::plural('Student', $students->count()) }} linked
            </span>
        </div>

        @if ($students->isEmpty())
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h3>No students linked yet</h3>
                <p>No student profile currently lists <strong>{{ $guardian->email }}</strong> as a Guardian Email. Ask the school to add it to a student's profile.</p>
            </div>
        @else
            <div class="guardian-student-grid">
                @foreach ($students as $student)
                    <a href="{{ route('guardian.students.show', $student) }}" class="guardian-student-card">
                        <div class="guardian-student-card-top">
                            <div class="guardian-student-card-avatar">{{ strtoupper(substr($student->student_name, 0, 1)) }}</div>
                            <i class="bi bi-chevron-right guardian-student-card-arrow"></i>
                        </div>
                        <h3>{{ $student->student_name }}</h3>
                        <div class="guardian-student-card-meta">
                            <span><i class="bi bi-people-fill"></i> {{ $student->schoolClass?->name ?? $student->class }}</span>
                            <span><i class="bi bi-building"></i> {{ $student->branch?->name ?? '—' }}</span>
                        </div>
                        @if ($student->session)
                            <span class="status-badge status-published mt-2">
                                <i class="bi bi-calendar-range"></i>
                                {{ $student->session->name }}
                            </span>
                        @endif
                        <div class="guardian-student-card-stats">
                            <div>
                                <strong>{{ $student->submitted_exams_count }}</strong>
                                <span>{{ Str::plural('Exam', $student->submitted_exams_count) }} Completed</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
