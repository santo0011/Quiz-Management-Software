<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Services\AcademicSessionResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $teacher = $request->user('teacher');
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        $baseQuery = $selectedSessionId
            ? ExamAttempt::where('branch_id', $teacher->branch_id)
                ->where('session_id', $selectedSessionId)
                ->where('status', 'submitted')
            : null;

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'selectedSessionId' => $selectedSessionId,
            'totalResults' => $baseQuery ? (clone $baseQuery)->count() : null,
            'remarkedCount' => $baseQuery ? (clone $baseQuery)->whereNotNull('teacher_remark')->count() : null,
            'pendingCount' => $baseQuery ? (clone $baseQuery)->whereNull('teacher_remark')->count() : null,
            'recentAttempts' => $baseQuery ? (clone $baseQuery)->with(['student', 'exam'])->latest('submitted_at')->take(8)->get() : collect(),
        ]);
    }
}
