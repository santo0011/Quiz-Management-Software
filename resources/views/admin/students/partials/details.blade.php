<dl class="detail-list">
    <div>
        <dt>Student Name</dt>
        <dd>{{ $student->student_name }}</dd>
    </div>
    <div>
        <dt>Guardian Name</dt>
        <dd>{{ $student->guardian_name }}</dd>
    </div>
    <div>
        <dt>Class</dt>
        <dd>{{ $student->class }}</dd>
    </div>
    <div>
        <dt>Phone Number</dt>
        <dd>{{ $student->phone_number }}</dd>
    </div>
    <div>
        <dt>Email Address</dt>
        <dd>{{ $student->email }}</dd>
    </div>
    <div>
        <dt>Branch</dt>
        <dd>{{ $student->branch->name }}</dd>
    </div>
    <div>
        <dt>Created At</dt>
        <dd>{{ $student->created_at->format('d M Y, h:i A') }}</dd>
    </div>
</dl>
