@if ($students->isEmpty())
    <div class="empty-state">
        <i class="bi bi-people"></i>
        <h3>No students found</h3>
        <p>Add a student or adjust the filters to see records.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table align-middle admin-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Guardian</th>
                    <th>Class</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($students as $student)
                    <tr>
                        <td><strong>{{ $student->student_name }}</strong></td>
                        <td>{{ $student->guardian_name }}</td>
                        <td>{{ $student->class }}</td>
                        <td>{{ $student->phone_number }}</td>
                        <td>{{ $student->email }}</td>
                        <td>
                            <span class="status-badge {{ $student->is_active ? 'status-published' : 'status-closed' }}">
                                <i class="bi {{ $student->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                                {{ $student->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="action-group">
                                <a href="{{ route('branch.students.show', $student) }}" class="btn btn-sm btn-soft" title="View">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-soft" title="Edit" data-bs-toggle="offcanvas" data-bs-target="#editStudentDrawer{{ $student->id }}" aria-controls="editStudentDrawer{{ $student->id }}">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <form method="POST" action="{{ route('branch.students.toggle-active', $student) }}" data-confirm-toggle>
                                    @csrf
                                    <button class="btn btn-sm {{ $student->is_active ? 'btn-danger-soft' : 'btn-soft' }}" type="submit" title="{{ $student->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $student->is_active ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('branch.students.destroy', $student) }}" data-confirm-delete>
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger-soft" type="submit" title="Delete">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $students->links() }}
@endif