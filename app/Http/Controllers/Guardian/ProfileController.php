<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $guardian = $request->user('guardian');

        $students = Student::where('guardian_email', $guardian->email)
            ->with(['branch', 'schoolClass'])
            ->orderBy('student_name')
            ->get();

        return view('guardian.profile', [
            'guardian' => $guardian,
            'students' => $students,
        ]);
    }
}
