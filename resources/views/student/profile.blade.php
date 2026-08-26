@extends('layouts.student')

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
                {{ strtoupper(substr($student->student_name, 0, 1)) }}
            </div>
            <div>
                <h3>{{ $student->student_name }}</h3>
                <p class="mb-0">{{ $student->email }}</p>
            </div>
        </div>

        <div class="student-details-grid mt-4">
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <dt>Full Name</dt>
                    <dd>{{ $student->student_name }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-person-heart"></i>
                </div>
                <div>
                    <dt>Guardian</dt>
                    <dd>{{ $student->guardian_name ?? '—' }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <dt>Branch</dt>
                    <dd>{{ $student->branch?->name }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <dt>Grade</dt>
                    <dd>{{ $student->schoolClass?->name ?? $student->class }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-book-fill"></i>
                </div>
                <div>
                    <dt>Subjects</dt>
                    <dd>
                        @forelse ($student->subjects as $subject)
                            <span class="badge text-bg-light border me-1">{{ $subject->name }}</span>
                        @empty
                            —
                        @endforelse
                    </dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-telephone-fill"></i>
                </div>
                <div>
                    <dt>Phone</dt>
                    <dd>{{ $student->phone_number ?? '—' }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd>{{ $student->email }}</dd>
                </div>
            </div>
        </div>
    </section>
@endsection