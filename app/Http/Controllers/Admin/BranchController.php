<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Mail\BranchCredentialsMail;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin.branches.index', [
            'branches' => Branch::with('user')->latest()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.branches.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BranchRequest $request): RedirectResponse
    {
        $temporaryPassword = (string) random_int(100000, 999999);

        $branch = DB::transaction(function () use ($request, $temporaryPassword) {
            $branch = Branch::create($request->safe()->only(['name', 'email']));

            User::create([
                'name' => $branch->name,
                'username' => $request->validated('username'),
                'email' => $branch->email,
                'role' => 'Branch',
                'branch_id' => $branch->id,
                'password' => Hash::make($temporaryPassword),
            ]);

            return $branch;
        });

        try {
            Mail::to($branch->email)->send(new BranchCredentialsMail($branch, $temporaryPassword, $request->validated('username')));
        } catch (\Throwable $e) {
            return redirect()->route('admin.branches.index')->with('error', 'Branch created, but login credentials could not be sent to the email.');
        }

        return redirect()->route('admin.branches.index')->with('success', 'Branch login credentials have been sent to the registered email.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch): View
    {
        return view('admin.branches.show', compact('branch'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch): View
    {
        return view('admin.branches.edit', compact('branch'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        DB::transaction(function () use ($request, $branch) {
            $branch->update($request->safe()->only(['name', 'email']));

            // Update the single loaded login row by its own id (never a
            // bulk UPDATE through the relation query).
            $attributes = [
                'name' => $branch->name,
                'username' => $request->validated('username'),
                'email' => $branch->email,
            ];

            if ($branch->user) {
                $branch->user->update($attributes);
            } else {
                // Older branches may have no login row yet: create it now
                // with a random password; the Super Admin sets a real one
                // with the Password button.
                User::create($attributes + [
                    'role' => 'Branch',
                    'branch_id' => $branch->id,
                    'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                ]);
            }
        });

        return redirect()->route('admin.branches.index')->with('success', 'Branch updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        $hasRelatedData = $branch->students()->exists()
            || $branch->teachers()->exists()
            || $branch->classes()->exists()
            || $branch->exams()->exists()
            || $branch->exams()->whereHas('attempts')->exists()
            || $branch->questionCategories()->exists();

        if ($hasRelatedData) {
            return redirect()->route('admin.branches.index')
                ->with('error', 'This branch cannot be deleted because related data exists. Please deactivate the branch instead.');
        }

        $branch->user()->delete();
        $branch->delete();

        return redirect()->route('admin.branches.index')->with('success', 'Branch deleted successfully.');
    }

    public function updatePassword(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        // Branch login looks the account up by its User ID, which lives on
        // this branch's own Branch-role users row.
        $account = $branch->user;

        if ($account) {
            $account->update([
                'password' => Hash::make($validated['password']),
            ]);

            return redirect()->route('admin.branches.index')->with('success', 'Branch password updated successfully.');
        }

        // A branch without a login account (e.g. added outside Admin →
        // Branches, which always creates one) has no User ID yet, so it
        // cannot log in — ask for one via Edit first.
        return redirect()->route('admin.branches.index')
            ->with('error', 'This branch has no login account yet. Edit the branch and set a User ID first, then set its password.');
    }

    /**
     * Live "is this User ID free?" check used while the Super Admin types
     * on the branch form. `ignore` is the branch being edited, if any.
     */
    public function checkUsername(Request $request): JsonResponse
    {
        $username = strtolower(trim((string) $request->query('username')));
        $ignoreUserId = $request->filled('ignore')
            ? Branch::find($request->integer('ignore'))?->user?->id
            : null;

        $validator = Validator::make(
            ['username' => $username],
            ['username' => [...BranchRequest::USERNAME_RULES, Rule::unique('users', 'username')->ignore($ignoreUserId)]],
            BranchRequest::usernameMessages(),
        );

        if ($validator->fails()) {
            return response()->json([
                'available' => false,
                'username' => $username,
                'message' => $validator->errors()->first('username'),
            ]);
        }

        return response()->json([
            'available' => true,
            'username' => $username,
            'message' => 'This User ID is available.',
        ]);
    }

    public function toggleActive(Branch $branch): RedirectResponse
    {
        $branch->update(['is_active' => ! $branch->is_active]);

        $message = $branch->is_active
            ? 'Branch activated successfully.'
            : 'Branch deactivated successfully.';

        return redirect()->route('admin.branches.index')->with('success', $message);
    }
}
