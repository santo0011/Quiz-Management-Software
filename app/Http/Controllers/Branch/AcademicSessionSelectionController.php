<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcademicSessionSelectionController extends Controller
{
    public const SESSION_KEY = 'branch_selected_academic_session_id';

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
        ], [
            'academic_session_id.required' => 'Please select a session.',
            'academic_session_id.exists' => 'The selected session could not be found.',
        ]);

        $request->session()->put(self::SESSION_KEY, (int) $validated['academic_session_id']);

        return redirect()->back()->with('success', 'Academic session switched successfully.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->back()->with('success', 'Academic session selection cleared.');
    }
}
