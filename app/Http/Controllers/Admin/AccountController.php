<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function updateEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->user()->id),
            ],
            'current_password' => ['required', 'current_password'],
        ], [
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'current_password.required' => 'Please enter your current password to confirm this change.',
            'current_password.current_password' => 'The current password is incorrect.',
        ]);

        $request->user()->update(['email' => $validated['email']]);

        return back()->with('success', 'Email address updated successfully.');
    }
}
