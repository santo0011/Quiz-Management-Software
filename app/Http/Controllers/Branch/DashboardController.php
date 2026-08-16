<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $branch = auth()->user()->branch;

        return view('branch.dashboard', [
            'branch' => $branch,
            'studentCount' => $branch ? Student::forBranch($branch->id)->count() : 0,
            'recentStudents' => $branch ? Student::forBranch($branch->id)->latest()->take(5)->get() : collect(),
        ]);
    }
}
