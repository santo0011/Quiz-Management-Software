@extends('layouts.guardian')

@section('title', 'My Profile')
@section('page-title', 'Profile')

@section('content')
    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>Account Information</span>
                <h2>My Profile</h2>
            </div>
        </div>

        <div class="student-profile-summary">
            <div class="student-profile-avatar-lg">
                <i class="bi bi-person-heart"></i>
            </div>
            <div>
                <h3>Guardian Account</h3>
                <p class="mb-0">{{ $guardian->email }}</p>
            </div>
        </div>

        <div class="student-details-grid mt-4">
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <div>
                    <dt>Registered Email</dt>
                    <dd>{{ $guardian->email }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <dt>Linked Students</dt>
                    <dd>{{ $students->count() }} {{ Str::plural('Student', $students->count()) }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <dt>Account Created</dt>
                    <dd>{{ $guardian->created_at?->format('d M Y') ?? '—' }}</dd>
                </div>
            </div>
        </div>
    </section>

    <section class="student-section mt-4">
        <div class="student-section-header">
            <div>
                <span>Linked Accounts</span>
                <h2>My Students</h2>
            </div>
        </div>

        @if ($students->isEmpty())
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h3>No students linked yet</h3>
                <p>Students whose Guardian Email matches this account will appear here.</p>
            </div>
        @else
            <div class="student-details-grid">
                @foreach ($students as $student)
                    <div class="student-detail-item">
                        <div class="student-detail-icon">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div>
                            <dt>{{ $student->student_name }}</dt>
                            <dd>{{ $student->schoolClass?->name ?? $student->class ?? '—' }} · {{ $student->branch?->name ?? '—' }}</dd>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
