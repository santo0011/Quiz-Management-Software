@extends('layouts.branch')

@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>Update Password</h2>
                <p>Replace your temporary login code with a secure password.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('branch.password.update') }}" class="admin-form">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <div class="password-field">
                    <input id="current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                    <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
                @error('current_password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <div class="password-field">
                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                <div class="password-field">
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required>
                    <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle-fill"></i>
                Save Password
            </button>
        </form>
    </section>
@endsection
