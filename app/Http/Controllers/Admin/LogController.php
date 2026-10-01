<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = LoginLog::query()
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->when($request->filled('login_method'), fn ($query) => $query->where('login_method', $request->string('login_method')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->date('date')))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            'filters' => $request->only(['role', 'login_method', 'status', 'date']),
            'roles' => ['Super Admin', 'Branch', 'Teacher', 'Student'],
            'loginMethods' => ['Password + OTP', 'OTP', 'Teacher Override'],
        ]);
    }
}
