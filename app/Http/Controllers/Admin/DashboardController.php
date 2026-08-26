<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student;
use App\Services\AcademicSessionResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        return view('admin.dashboard', [
            'branchCount' => Branch::count(),
            'selectedSessionId' => $selectedSessionId,
            'studentCount' => $selectedSessionId ? Student::where('session_id', $selectedSessionId)->count() : null,
            'recentBranches' => Branch::latest()->take(5)->get(),
        ]);
    }
}
