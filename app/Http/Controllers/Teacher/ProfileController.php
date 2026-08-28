<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('teacher.profile', [
            'teacher' => $request->user('teacher')->load('branch'),
        ]);
    }
}
