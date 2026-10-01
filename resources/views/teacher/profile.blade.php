@extends('layouts.teacher')

@section('title', 'My Profile')
@section('page-title', 'Profile')

@section('content')
    <section class="student-section">
        <div class="student-section-header">
            <div>
                <span>Account Information</span>
                <h2>My Profile</h2>
            </div>
            <a href="{{ route('teacher.password.edit') }}" class="btn btn-sm btn-soft">
                <i class="bi bi-shield-lock-fill"></i>
                Change Password
            </a>
        </div>

        <div class="student-profile-summary">
            <div class="student-profile-avatar-lg">
                {{ strtoupper(substr($teacher->name, 0, 1)) }}
            </div>
            <div>
                <h3>{{ $teacher->name }}</h3>
                <p class="mb-0">Teacher · {{ $teacher->branch?->name ?? '—' }}</p>
            </div>
        </div>

        <div class="student-details-grid mt-4">
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <dt>Full Name</dt>
                    <dd>{{ $teacher->name }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd>{{ $teacher->email }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-telephone-fill"></i>
                </div>
                <div>
                    <dt>Phone Number</dt>
                    <dd>{{ $teacher->phone_number ?: '—' }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <dt>Branch</dt>
                    <dd>{{ $teacher->branch?->name ?? '—' }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <dt>Account Created</dt>
                    <dd>{{ $teacher->created_at?->format('d M Y') ?? '—' }}</dd>
                </div>
            </div>
        </div>
    </section>

    <section class="student-section mt-4">
        <div class="student-section-header">
            <div>
                <span>Results of students in your branch</span>
                <h2>My Review Activity</h2>
            </div>
            <a href="{{ route('teacher.results.index') }}" class="btn btn-sm btn-soft">
                <i class="bi bi-bar-chart-fill"></i>
                View Results
            </a>
        </div>

        <div class="student-details-grid">
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-clipboard-data-fill"></i>
                </div>
                <div>
                    <dt>Total Results</dt>
                    <dd>{{ $totalResults }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-chat-square-text-fill"></i>
                </div>
                <div>
                    <dt>Remarks Given</dt>
                    <dd>{{ $remarkedCount }}</dd>
                </div>
            </div>
            <div class="student-detail-item">
                <div class="student-detail-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <dt>Pending Remarks</dt>
                    <dd>{{ $pendingCount }}</dd>
                </div>
            </div>
        </div>
    </section>
@endsection
