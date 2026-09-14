<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.dashboard', [
            'branchCount' => Branch::count(),
            'studentCount' => Student::count(),
            'recentBranches' => Branch::latest()->take(5)->get(),
        ]);
    }
}
