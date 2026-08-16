@php($prefix = $prefix ?? 'admin')
@php($contextBranch = $selectedBranch ?? $branch)

<section class="student-profile-hero">
    <div class="student-profile-overlay"></div>
    <div class="student-profile-content">
        <div class="student-profile-top">
            <div class="student-profile-badges">
                <span class="student-profile-chip">
                    <i class="bi bi-building"></i>
                    {{ $contextBranch->name }}
                </span>
                @if ($student->class)
                    <span class="student-profile-chip">
                        <i class="bi bi-people-fill"></i>
                        {{ $student->class }}
                    </span>
                @endif
            </div>
            <div class="student-profile-actions">
                <button type="button" class="btn btn-light btn-student-action" data-bs-toggle="offcanvas" data-bs-target="#editStudentDrawer{{ $student->id }}">
                    <i class="bi bi-pencil-fill"></i>
                    Edit Student
                </button>
            </div>
        </div>
        <div class="student-profile-main">
            <div class="student-profile-avatar">
                {{ strtoupper(substr($student->student_name, 0, 1)) }}
            </div>
            <div class="student-profile-info">
                <h1 class="student-profile-name">{{ $student->student_name }}</h1>
                <p class="student-profile-subtitle">
                    <i class="bi bi-person-badge"></i>
                    Student ID: #{{ str_pad($student->id, 4, '0', STR_PAD_LEFT) }}
                </p>
            </div>
        </div>
    </div>
</section>

<section class="student-stats-grid">
    <div class="student-stat-card">
        <div class="student-stat-icon primary">
            <i class="bi bi-person-fill"></i>
        </div>
        <div class="student-stat-body">
            <span>Guardian</span>
            <strong>{{ $student->guardian_name ?? '—' }}</strong>
        </div>
    </div>
    <div class="student-stat-card">
        <div class="student-stat-icon accent">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="student-stat-body">
            <span>Class</span>
            <strong>{{ $student->class ?? '—' }}</strong>
        </div>
    </div>
    <div class="student-stat-card">
        <div class="student-stat-icon success">
            <i class="bi bi-telephone-fill"></i>
        </div>
        <div class="student-stat-body">
            <span>Phone</span>
            <strong>{{ $student->phone_number ?? '—' }}</strong>
        </div>
    </div>
    <div class="student-stat-card">
        <div class="student-stat-icon info">
            <i class="bi bi-envelope-fill"></i>
        </div>
        <div class="student-stat-body">
            <span>Email</span>
            <strong>{{ $student->email ?? '—' }}</strong>
        </div>
    </div>
</section>

<section class="content-panel student-details-panel">
    <div class="panel-header">
        <div>
            <h2><i class="bi bi-person-vcard me-2 text-primary"></i>Student Information</h2>
            <p>Complete profile details for this student.</p>
        </div>
    </div>

    <div class="student-details-grid">
        <div class="student-detail-item">
            <div class="student-detail-icon">
                <i class="bi bi-person-fill"></i>
            </div>
            <div>
                <dt>Student Name</dt>
                <dd>{{ $student->student_name }}</dd>
            </div>
        </div>
        <div class="student-detail-item">
            <div class="student-detail-icon">
                <i class="bi bi-person-heart"></i>
            </div>
            <div>
                <dt>Guardian Name</dt>
                <dd>{{ $student->guardian_name ?? '—' }}</dd>
            </div>
        </div>
        <div class="student-detail-item">
            <div class="student-detail-icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <dt>Class</dt>
                <dd>{{ $student->class ?? '—' }}</dd>
            </div>
        </div>
        <div class="student-detail-item">
            <div class="student-detail-icon">
                <i class="bi bi-telephone-fill"></i>
            </div>
            <div>
                <dt>Phone Number</dt>
                <dd>{{ $student->phone_number ?? '—' }}</dd>
            </div>
        </div>
        <div class="student-detail-item">
            <div class="student-detail-icon">
                <i class="bi bi-envelope-fill"></i>
            </div>
            <div>
                <dt>Email Address</dt>
                <dd>{{ $student->email ?? '—' }}</dd>
            </div>
        </div>
        <div class="student-detail-item">
            <div class="student-detail-icon">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <dt>Branch</dt>
                <dd>{{ $contextBranch->name }}</dd>
            </div>
        </div>
        <div class="student-detail-item">
            <div class="student-detail-icon">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <dt>Created At</dt>
                <dd>{{ $student->created_at?->format('d M Y, h:i A') }}</dd>
            </div>
        </div>
    </div>
</section>

<div class="d-flex gap-2 flex-wrap student-profile-footer">
    <a href="{{ route($prefix.'.students.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
        Back to Students
    </a>
    <form method="POST" action="{{ route($prefix.'.students.destroy', $student) }}" data-confirm-delete>
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger" type="submit">
            <i class="bi bi-trash-fill"></i>
            Delete Student
        </button>
    </form>
</div>