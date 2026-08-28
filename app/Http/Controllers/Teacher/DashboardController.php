<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $teacher = $request->user('teacher');

        $baseQuery = ExamAttempt::where('branch_id', $teacher->branch_id)->where('status', 'submitted');

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'totalResults' => (clone $baseQuery)->count(),
            'remarkedCount' => (clone $baseQuery)->whereNotNull('teacher_remark')->count(),
            'pendingCount' => (clone $baseQuery)->whereNull('teacher_remark')->count(),
            'recentAttempts' => (clone $baseQuery)->with(['student', 'exam'])->latest('submitted_at')->take(8)->get(),
        ]);
    }
}
