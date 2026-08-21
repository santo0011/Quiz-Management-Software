@php($prefix = $prefix ?? 'admin')

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
                    <th>Exam</th>
                    <th>Class</th>
                    <th>Questions</th>
                    <th>Marks</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($exams as $exam)
                    <tr>
                        <td>
                            <strong>{{ $exam->title }}</strong>
                            <span class="table-subtext">{{ $exam->duration_minutes }} minutes</span>
                        </td>
                        <td>{{ $exam->schoolClass?->name }}</td>
                        <td>{{ $exam->questions_count ?? $exam->questions->count() }}</td>
                        <td>{{ $exam->total_marks }}</td>
                        <td>
                            @if ($exam->isPublished())
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
                                @if (!$exam->hasBeenAttempted())
                                    <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-sm btn-soft" title="Add Question"><i class="bi bi-patch-plus-fill"></i></a>
                                    <a href="{{ route($prefix.'.exams.edit', $exam) }}" class="btn btn-sm btn-soft" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                    <form method="POST" action="{{ route($prefix.'.exams.destroy', $exam) }}" data-confirm-delete>
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger-soft" type="submit" title="Delete"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @else
                                    <span class="publish-lock-hint" data-bs-toggle="tooltip" data-bs-title="{{ \App\Models\Exam::LOCK_MESSAGE }}">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
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