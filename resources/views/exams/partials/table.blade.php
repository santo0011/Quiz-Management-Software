@php($prefix = $prefix ?? 'admin')
{{-- Scope (Global / branch) is shown to Super Admin only — hidden on the Branch exam list. --}}
@php($showScope = $prefix !== 'branch')

@if ($exams->isEmpty())
    <div class="empty-state">
        <i class="bi bi-journal-check"></i>
        <h3>No exams found</h3>
        <p>Create a draft exam, add MCQ questions, then publish it for students.</p>
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
                    @if ($showScope)
                        <th>Scope</th>
                    @endif
                    <th>Questions</th>
                    <th>Marks</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($exams as $exam)
                    @php($canManage = $prefix === 'admin' || $exam->branch_id !== null)
                    <tr>
                        <td>{{ $exams->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $exam->title }}</strong>
                        </td>
                        <td>{{ $exam->subject?->name ?? '—' }}</td>
                        <td><span class="exam-grade-list">{{ $exam->gradeNames() ?: '—' }}</span></td>
                        @if ($showScope)
                            <td>
                                @if ($exam->isGlobal())
                                    <span class="scope-badge scope-global">
                                        <i class="bi bi-globe2"></i>
                                        {{ $exam->visibilityLabel() }}
                                    </span>
                                @else
                                    {{-- Hover shows which branch created this exam. --}}
                                    <span class="scope-badge scope-branch"
                                          data-bs-toggle="tooltip"
                                          data-bs-title="Created by {{ $exam->createdByLabel() }}"
                                          aria-label="Created by {{ $exam->createdByLabel() }}">
                                        <i class="bi bi-building"></i>
                                        {{ $exam->visibilityLabel() }}
                                    </span>
                                @endif
                            </td>
                        @endif
                        <td>{{ $exam->questions_count ?? $exam->questions->count() }}</td>
                        <td>{{ $exam->total_marks }}</td>
                        <td>
                            @if (! $canManage)
                                @if ($exam->isPublished())
                                    <span class="status-badge status-published">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Published
                                    </span>
                                @elseif ($exam->status === 'closed')
                                    <span class="status-badge status-closed">
                                        <i class="bi bi-x-circle-fill"></i>
                                        Closed
                                    </span>
                                @else
                                    <span class="status-badge status-draft-btn">
                                        <i class="bi bi-rocket-takeoff"></i>
                                        Draft
                                    </span>
                                @endif
                            @elseif ($exam->isPublished())
                                <form method="POST" action="{{ route($prefix.'.exams.unpublish', $exam) }}" data-unpublish-exam>
                                    @csrf
                                    <button type="submit" class="status-badge status-published" title="Click to unpublish this exam">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Published
                                    </button>
                                </form>
                            @elseif ($exam->status === 'closed')
                                <span class="status-badge status-closed">
                                    <i class="bi bi-x-circle-fill"></i>
                                    Closed
                                </span>
                            @else
                                <form method="POST" action="{{ route($prefix.'.exams.publish', $exam) }}" data-publish-exam>
                                    @csrf
                                    <button type="submit" class="status-badge status-draft-btn" title="Click to publish this exam">
                                        <i class="bi bi-rocket-takeoff"></i>
                                        Draft
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="action-group">
                                <a href="{{ route($prefix.'.exams.show', $exam) }}" class="btn btn-sm btn-soft" title="View"><i class="bi bi-eye-fill"></i></a>
                                @if (! $canManage)
                                    <span class="publish-lock-hint" data-bs-toggle="tooltip" data-bs-title="Managed by the Super Admin.">
                                        <i class="bi bi-globe2"></i>
                                    </span>
                                @else
                                    <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-sm btn-soft" title="Add Question"><i class="bi bi-patch-plus-fill"></i></a>
                                    <a href="{{ route($prefix.'.exams.edit', $exam) }}" class="btn btn-sm btn-soft" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                    @if (!$exam->hasBeenAttempted())
                                        <form method="POST" action="{{ route($prefix.'.exams.destroy', $exam) }}" data-confirm-delete>
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger-soft" type="submit" title="Delete"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    @else
                                        <span class="publish-lock-hint" data-bs-toggle="tooltip" data-bs-title="{{ \App\Models\Exam::DELETE_LOCK_MESSAGE }}">
                                            <i class="bi bi-lock-fill"></i>
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $exams->links() }}
@endif