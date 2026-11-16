@extends('layouts.teacher')

@section('title', 'Exams')
@section('page-title', 'Exams')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $teacher->branch?->name }} Exams</h2>
                <p>Exams for your branch and their questions. View only.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('teacher.exams.index') }}" class="row g-2 mb-3">
            <div class="col-md-6">
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search exam title">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-soft"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>

        @if ($exams->isEmpty())
            <div class="empty-state">
                <i class="bi bi-journal-check"></i>
                <h3>No exams found</h3>
                <p>Exams for your branch will appear here.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Exam Name</th>
                            <th>Subject</th>
                            <th>Grade(s)</th>
                            <th>Questions</th>
                            <th>Marks</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exams as $exam)
                            <tr>
                                <td>{{ $exams->firstItem() + $loop->index }}</td>
                                <td>
                                    <strong>{{ $exam->title }}</strong>
                                    <div class="small text-muted">
                                        <i class="bi {{ $exam->isGlobal() ? 'bi-globe2' : 'bi-building' }}"></i>
                                        {{ $exam->isGlobal() ? 'All branches' : 'This branch' }}
                                    </div>
                                </td>
                                <td>{{ $exam->subject?->name ?? '—' }}</td>
                                <td><span class="exam-grade-list">{{ $exam->gradeNames() ?: '—' }}</span></td>
                                <td>{{ $exam->questions_count }}</td>
                                <td>{{ $exam->total_marks }}</td>
                                <td>
                                    <span class="status-badge {{ ['published' => 'status-published', 'closed' => 'status-closed'][$exam->status] ?? 'status-draft-btn' }}">{{ ucfirst($exam->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('teacher.exams.show', $exam) }}" class="btn btn-sm btn-soft" title="View questions">
                                        <i class="bi bi-eye-fill"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $exams->links() }}</div>
        @endif
    </section>
@endsection
