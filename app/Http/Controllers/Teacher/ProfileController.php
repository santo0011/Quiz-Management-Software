<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $teacher = $request->user('teacher')->load('branch');

        // Same Branch-owned results as Teacher\ResultController::index().
        $baseQuery = ExamAttempt::belongingToBranch($teacher->branch_id ? (int) $teacher->branch_id : null);

        return view('teacher.profile', [
            'teacher' => $teacher,
            'totalResults' => (clone $baseQuery)->count(),
            'remarkedCount' => (clone $baseQuery)->whereNotNull('teacher_remark')->count(),
            'pendingCount' => (clone $baseQuery)->whereNull('teacher_remark')->count(),
        ]);
    }
}
