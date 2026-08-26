@extends('layouts.admin')

@section('title', 'Session Details')
@section('page-title', 'Session Details')

@section('content')
    <div class="student-profile-top-actions">
        <a href="{{ route('admin.academic-sessions.index') }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>

    <section class="exam-hero">
        <div class="exam-hero-overlay"></div>
        <div class="exam-hero-content">
            <div class="exam-hero-top">
                <div class="exam-hero-badges">
                    <span class="exam-status-badge status-{{ $academicSession->is_active ? 'published' : 'closed' }}">
                        <i class="bi {{ $academicSession->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                        {{ $academicSession->is_active ? 'Active' : 'Closed' }}
                    </span>
                    <span class="exam-meta-chip">
                        <i class="bi bi-calendar-event"></i>
                        {{ $academicSession->start_date->format('d M Y') }} &ndash; {{ $academicSession->end_date->format('d M Y') }}
                    </span>
                </div>
                <div class="exam-hero-actions">
                    <button type="button" class="btn btn-light btn-exam-action" data-bs-toggle="offcanvas" data-bs-target="#editSessionDrawer{{ $academicSession->id }}" aria-controls="editSessionDrawer{{ $academicSession->id }}">
                        <i class="bi bi-pencil-fill"></i>
                        Edit Session
                    </button>
                    <form method="POST" action="{{ route('admin.academic-sessions.toggle-active', $academicSession) }}" data-confirm-toggle>
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-exam-action" title="{{ $academicSession->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="bi {{ $academicSession->is_active ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' }}"></i>
                            {{ $academicSession->is_active ? 'Close Session' : 'Activate Session' }}
                        </button>
                    </form>
                </div>
            </div>
            <h1 class="exam-hero-title">{{ $academicSession->name }}</h1>
            @if ($academicSession->description)
                <p class="exam-hero-desc">{{ $academicSession->description }}</p>
            @endif
        </div>
    </section>

    <section class="exam-stats-grid">
        <div class="exam-stat-card">
            <div class="exam-stat-icon primary">
                <i class="bi bi-calendar-range"></i>
            </div>
            <div class="exam-stat-body">
                <span>Duration</span>
                <strong>{{ $academicSession->start_date->diffInDays($academicSession->end_date) }} <small>days</small></strong>
            </div>
        </div>
        <div class="exam-stat-card">
            <div class="exam-stat-icon success">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="exam-stat-body">
                <span>Students</span>
                <strong>{{ $academicSession->students()->count() }}</strong>
            </div>
        </div>
        <div class="exam-stat-card">
            <div class="exam-stat-icon warning">
                <i class="bi bi-journal-check"></i>
            </div>
            <div class="exam-stat-body">
                <span>Exams</span>
                <strong>{{ $academicSession->exams()->count() }}</strong>
            </div>
        </div>
        <div class="exam-stat-card">
            <div class="exam-stat-icon info">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="exam-stat-body">
                <span>Created</span>
                <strong>{{ $academicSession->created_at->format('d M Y') }}</strong>
            </div>
        </div>
    </section>

    <section class="content-panel exam-details-panel">
        <div class="panel-header">
            <div>
                <h2><i class="bi bi-info-circle me-2 text-primary"></i>Session Information</h2>
                <p>Full record details for this academic session.</p>
            </div>
        </div>

        <div class="exam-details-grid">
            <div class="exam-detail-item">
                <div class="exam-detail-icon">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <dt>Start Date</dt>
                    <dd>{{ $academicSession->start_date->format('d M Y') }}</dd>
                </div>
            </div>
            <div class="exam-detail-item">
                <div class="exam-detail-icon">
                    <i class="bi bi-calendar-x"></i>
                </div>
                <div>
                    <dt>End Date</dt>
                    <dd>{{ $academicSession->end_date->format('d M Y') }}</dd>
                </div>
            </div>
            <div class="exam-detail-item">
                <div class="exam-detail-icon {{ $academicSession->is_active ? 'success' : 'danger' }}">
                    <i class="bi {{ $academicSession->is_active ? 'bi-shield-check' : 'bi-exclamation-triangle-fill' }}"></i>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>{{ $academicSession->is_active ? 'Active' : 'Closed' }}</dd>
                </div>
            </div>
            <div class="exam-detail-item">
                <div class="exam-detail-icon">
                    <i class="bi bi-card-text"></i>
                </div>
                <div>
                    <dt>Description</dt>
                    <dd>{{ $academicSession->description ?: '—' }}</dd>
                </div>
            </div>
        </div>
    </section>

    <div class="offcanvas offcanvas-end student-drawer" tabindex="-1" id="editSessionDrawer{{ $academicSession->id }}" aria-labelledby="editSessionDrawerLabel{{ $academicSession->id }}">
        <div class="offcanvas-header student-drawer-header">
            <div>
                <span class="page-kicker">Session Management</span>
                <h2 class="offcanvas-title" id="editSessionDrawerLabel{{ $academicSession->id }}">Edit Session</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('admin.academic-sessions.partials.form', [
                'academicSession' => $academicSession,
                'action' => route('admin.academic-sessions.update', $academicSession),
                'method' => 'PUT',
                'button' => 'Update Session',
                'drawer' => true,
                'drawerId' => 'editSessionDrawer'.$academicSession->id,
            ])
        </div>
    </div>
@endsection
