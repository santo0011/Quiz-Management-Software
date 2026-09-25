<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Mail\BranchCredentialsMail;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin.branches.index', [
            'branches' => Branch::latest()->paginate(20),
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
            $branch = Branch::create($request->validated());

            User::create([
                'name' => $branch->name,
                'email' => $branch->email,
                'role' => 'Branch',
                'branch_id' => $branch->id,
                'password' => Hash::make($temporaryPassword),
            ]);

            return $branch;
        });

        try {
            Mail::to($branch->email)->send(new BranchCredentialsMail($branch, $temporaryPassword));
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
            $branch->update($request->validated());

            // $branch->user() (the relation query builder) would issue a
            // bulk UPDATE scoped only by branch_id — if more than one users
            // row ever shares that branch_id, it updates all of them to the
            // same email/name in one statement, tripping the email unique
            // constraint on the second row. Loading the single related model
            // first scopes the update to that one row's own id instead.
            $branch->user?->update([
                'name' => $branch->name,
                'email' => $branch->email,
            ]);
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

        // Branch login looks the account up by email (LoginController), so
        // update that exact row. $branch->user (hasOne by branch_id) can
        // resolve to a different users row when several share a branch_id,
        // which silently left the real login account on its old password.
        $account = User::where('email', $branch->email)->where('role', 'Branch')->first() ?? $branch->user;

        abort_if(! $account, 404, 'This branch has no linked login account.');

        $account->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.branches.index')->with('success', 'Branch password updated successfully.');
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
