<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Mail\BranchCredentialsMail;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
            'branches' => Branch::latest()->paginate(10),
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

        Mail::to($branch->email)->send(new BranchCredentialsMail($branch, $temporaryPassword));

        return redirect()->route('admin.branches.index')->with('success', 'Branch created and login code sent successfully.');
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

            $branch->user()->update([
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
        $branch->user()->delete();
        $branch->delete();

        return redirect()->route('admin.branches.index')->with('success', 'Branch deleted successfully.');
    }
}
