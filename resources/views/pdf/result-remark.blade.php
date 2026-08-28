<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exam Result</title>
    <style>
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #1e293b;
            font-size: 12px;
        }
        .header {
            background: #1e293b;
            color: #ffffff;
            padding: 18px 24px;
            border-radius: 6px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 4px 0 0;
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e293b;
            margin: 22px 0 8px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 6px;
        }
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.info-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
        }
        table.info-table td.label {
            width: 35%;
            background: #f8fafc;
            font-weight: bold;
            color: #475569;
        }
        table.stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.stats-table th, table.stats-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            text-align: center;
            font-size: 12px;
        }
        table.stats-table th {
            background: #f1f5f9;
            color: #475569;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
        }
        .badge-pass {
            background: #dcfce7;
            color: #166534;
        }
        .badge-fail {
            background: #fee2e2;
            color: #991b1b;
        }
        .remark-box {
            background: #f0f9ff;
            border: 1px solid #38bdf8;
            border-radius: 6px;
            padding: 14px 16px;
            margin-top: 8px;
            font-size: 12px;
            color: #0c4a6e;
            line-height: 1.6;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>QuizCore</h1>
        <p>Exam Result Report</p>
    </div>

    <div class="section-title">Student & Exam Details</div>
    <table class="info-table">
        <tr>
            <td class="label">Student Name</td>
            <td>{{ $attempt->student?->student_name }}</td>
        </tr>
        <tr>
            <td class="label">Grade</td>
            <td>{{ $attempt->schoolClass?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Exam</td>
            <td>{{ $attempt->exam?->title }}</td>
        </tr>
        <tr>
            <td class="label">Submitted On</td>
            <td>{{ $attempt->submitted_at?->format('d M Y, h:i A') ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Result Summary</div>
    <table class="stats-table">
        <tr>
            <th>Total Marks</th>
            <th>Obtained</th>
            <th>Percentage</th>
            <th>Correct</th>
            <th>Wrong</th>
            <th>Unanswered</th>
            <th>Status</th>
        </tr>
        <tr>
            <td>{{ $attempt->exam?->total_marks }}</td>
            <td>{{ $attempt->obtained_marks }}</td>
            <td>{{ $attempt->percentage }}%</td>
            <td>{{ $attempt->correct_count }}</td>
            <td>{{ $attempt->wrong_count }}</td>
            <td>{{ $attempt->unanswered_count }}</td>
            <td>
                <span class="badge {{ $attempt->is_passed ? 'badge-pass' : 'badge-fail' }}">
                    {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
                </span>
            </td>
        </tr>
    </table>

    <div class="section-title">Teacher Remark</div>
    <div class="remark-box">
        {{ $attempt->teacher_remark }}
    </div>
    <table class="info-table" style="margin-top:10px;">
        <tr>
            <td class="label">Remark By</td>
            <td>{{ $attempt->teacherRemarkBy?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Remark Date</td>
            <td>{{ $attempt->teacher_remark_at?->format('d M Y, h:i A') ?? '—' }}</td>
        </tr>
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} QuizCore &middot; Quiz Management Software &middot; This is a system-generated report.
    </div>
</body>
</html>
