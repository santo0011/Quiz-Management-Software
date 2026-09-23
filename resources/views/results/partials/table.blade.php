@php($prefix = $prefix ?? 'admin')

@if ($attempts->isEmpty())
    <div class="empty-state">
        <i class="bi bi-bar-chart"></i>
        <h3>No results found</h3>
        <p>Submitted exam attempts will appear here.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table align-middle admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Exam</th>
                    <th>Marks</th>
                    <th>Percentage</th>
                    <th>Attempt Date</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attempts as $attempt)
                    <tr>
                        <td>{{ $attempts->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $attempt->student?->student_name }}</strong>
                            <span class="table-subtext">{{ $attempt->student?->email }}</span>
                        </td>
                        <td>{{ $attempt->schoolClass?->name }}</td>
                        <td>{{ $attempt->exam?->title }}</td>
                        <td>{{ $attempt->obtained_marks }} / {{ $attempt->exam?->total_marks }}</td>
                        <td>{{ $attempt->percentage }}%</td>
                        <td>{{ $attempt->submitted_at?->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route($prefix.'.results.show', $attempt) }}" class="btn btn-sm btn-soft"><i class="bi bi-eye-fill"></i></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $attempts->links() }}
@endif
