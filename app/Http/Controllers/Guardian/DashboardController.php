<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $guardian = $request->user('guardian');

        $students = Student::where('guardian_email', $guardian->email)
            ->with(['branch', 'schoolClass', 'session'])
            ->withCount(['attempts as submitted_exams_count' => fn ($query) => $query->where('status', 'submitted')])
            ->orderBy('student_name')
            ->get();

        return view('guardian.dashboard', [
            'guardian' => $guardian,
            'students' => $students,
        ]);
    }
}
