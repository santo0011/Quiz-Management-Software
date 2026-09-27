@extends('layouts.teacher')

@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>Update Password</h2>
                <p>Choose a strong password you don't use anywhere else.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('teacher.password.update') }}" class="admin-form">
            @csrf
            @method('PUT')

            {{-- All three fields on one line; they stack on small screens. --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
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

                <div class="col-md-4">
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

                <div class="col-md-4">
                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                    <div class="password-field">
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required>
                        <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle-fill"></i>
                    Save Password
                </button>
            </div>
        </form>
    </section>
@endsection
