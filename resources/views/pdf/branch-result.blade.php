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
            padding: 26px 28px;
            border-radius: 10px;
        }
        .header-top {
            width: 100%;
        }
        .header-top td {
            vertical-align: middle;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 4px 0 0;
            font-size: 11px;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e293b;
            margin: 24px 0 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.info-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
        }
        table.info-table td.label {
            width: 32%;
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
            padding: 12px 10px;
            text-align: center;
        }
        table.stats-table th {
            background: #1e293b;
            color: #f1f5f9;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.6px;
        }
        table.stats-table td {
            font-size: 15px;
            font-weight: bold;
            color: #1e293b;
        }
        table.stats-table td.percentage {
            color: #1d4ed8;
        }
        .footer {
            margin-top: 34px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }
        .disclaimer {
            margin-top: 14px;
            font-size: 10px;
            color: #94a3b8;
            font-style: italic;
        }
        .review-box {
            background: #f0f9ff;
            border: 1px solid #38bdf8;
            border-radius: 6px;
            padding: 14px 16px;
            margin-top: 8px;
            font-size: 12px;
            color: #0c4a6e;
            line-height: 1.6;
            white-space: pre-line;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-top">
            <tr>
                <td>
                    <h1>QuizCore</h1>
                    <p>Official Academic Result Report</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Student Details</div>
    <table class="info-table">
        <tr>
            <td class="label">Student Name</td>
            <td>{{ $attempt->student?->student_name }}</td>
        </tr>
        <tr>
            <td class="label">Student ID</td>
            <td>{{ $attempt->student?->zoho_student_id ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Class</td>
            <td>{{ $attempt->zoho_class_name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Grade</td>
            <td>{{ $attempt->zoho_grade ?? $attempt->schoolClass?->name ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Exam Details</div>
    <table class="info-table">
        <tr>
            <td class="label">Exam Title</td>
            <td>{{ $attempt->exam?->title }}</td>
        </tr>
        <tr>
            <td class="label">Subject</td>
            <td>{{ $attempt->exam?->subject?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Attempt</td>
            <td>#{{ $attempt->attempt_number }}</td>
        </tr>
        <tr>
            <td class="label">Submitted On</td>
            <td>{{ $attempt->submitted_at?->format('d M Y') ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Result Summary</div>
    <table class="stats-table">
        <tr>
            <th>Total Marks</th>
            <th>Obtained Marks</th>
            <th>Percentage</th>
        </tr>
        <tr>
            <td>{{ $attempt->exam?->total_marks }}</td>
            <td>{{ $attempt->obtained_marks }}</td>
            <td class="percentage">{{ $attempt->percentage }}%</td>
        </tr>
    </table>
    <table class="stats-table" style="margin-top:10px;">
        <tr>
            <th>Correct</th>
            <th>Wrong</th>
            <th>Unanswered</th>
        </tr>
        <tr>
            <td>{{ $attempt->correct_count }}</td>
            <td>{{ $attempt->wrong_count }}</td>
            <td>{{ $attempt->unanswered_count }}</td>
        </tr>
    </table>

    @if ($attempt->branch_review)
        <div class="section-title">Feedback</div>
        <div class="review-box">{{ $attempt->branch_review }}</div>
    @endif

    <p class="disclaimer">This result reflects exactly what was recorded by the system at the time of submission and has not been recalculated.</p>

    <div class="footer">
        &copy; {{ date('Y') }} QuizCore &middot; Quiz Management Software &middot; This is a system-generated official result report.
    </div>
</body>
</html>
