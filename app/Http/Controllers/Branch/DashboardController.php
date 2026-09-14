<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $branch = auth()->user()->branch;

        $branchStudents = $branch ? Student::forBranch($branch->id) : null;

        return view('branch.dashboard', [
            'branch' => $branch,
            'studentCount' => $branchStudents ? (clone $branchStudents)->count() : null,
            'recentStudents' => $branchStudents ? (clone $branchStudents)->latest()->take(5)->get() : collect(),
        ]);
    }
}
