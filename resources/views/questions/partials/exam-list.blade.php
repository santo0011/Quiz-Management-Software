@if ($exams->isEmpty())
    <div class="empty-state">
        <i class="bi bi-patch-question"></i>
        <h3>No exams available</h3>
        <p>Create an exam first, then add MCQ questions under it.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table align-middle admin-table">
            <thead>
                <tr>
                    <th>Exam</th>
                    <th>Grade</th>
                    <th>Questions</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($exams as $exam)
                    <tr>
                        <td><strong>{{ $exam->title }}</strong></td>
                        <td>{{ $exam->schoolClass?->name }}</td>
                        <td>{{ $exam->questions_count }}</td>
                        <td><span class="status-badge status-{{ $exam->status }}">{{ ucfirst($exam->status) }}</span></td>
                        <td class="text-end">
                            <div class="action-group">
                                <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-circle-fill"></i> Add Question</a>
                                <a href="{{ route($prefix.'.exams.show', $exam) }}" class="btn btn-sm btn-soft"><i class="bi bi-eye-fill"></i></a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $exams->links() }}
@endif
