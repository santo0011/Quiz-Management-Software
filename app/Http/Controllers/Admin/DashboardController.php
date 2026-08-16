<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $selectedBranch = session('admin_selected_branch_id')
            ? Branch::find(session('admin_selected_branch_id'))
            : null;

        return view('admin.dashboard', [
            'branchCount' => Branch::count(),
            'studentCount' => Student::count(),
            'selectedBranch' => $selectedBranch,
            'selectedBranchStudentCount' => $selectedBranch ? Student::forBranch($selectedBranch->id)->count() : 0,
            'recentBranches' => Branch::latest()->take(5)->get(),
        ]);
    }
}
