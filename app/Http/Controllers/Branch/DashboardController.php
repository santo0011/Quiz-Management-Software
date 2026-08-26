<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\AcademicSessionResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $branch = auth()->user()->branch;
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        $sessionScoped = $branch && $selectedSessionId
            ? Student::forBranch($branch->id)->where('session_id', $selectedSessionId)
            : null;

        return view('branch.dashboard', [
            'branch' => $branch,
            'selectedSessionId' => $selectedSessionId,
            'studentCount' => $sessionScoped ? (clone $sessionScoped)->count() : null,
            'recentStudents' => $sessionScoped ? (clone $sessionScoped)->latest()->take(5)->get() : collect(),
        ]);
    }
}
