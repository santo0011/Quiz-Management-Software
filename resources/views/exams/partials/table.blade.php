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
                        <td><span class="status-badge status-{{ $exam->status }}">{{ ucfirst($exam->status) }}</span></td>
                        <td class="text-end">
                            <div class="action-group">
                                <a href="{{ route($prefix.'.exams.show', $exam) }}" class="btn btn-sm btn-soft" title="View"><i class="bi bi-eye-fill"></i></a>
                                <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-sm btn-soft" title="Add Question"><i class="bi bi-patch-plus-fill"></i></a>
                                <a href="{{ route($prefix.'.exams.edit', $exam) }}" class="btn btn-sm btn-soft" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                <form method="POST" action="{{ route($prefix.'.exams.destroy', $exam) }}" data-confirm-delete>
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger-soft" type="submit" title="Delete"><i class="bi bi-trash-fill"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $exams->links() }}
@endif
