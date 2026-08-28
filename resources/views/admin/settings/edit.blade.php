@extends('layouts.admin')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
    <div class="settings-hero">
        <div class="settings-hero-icon">
            <i class="bi bi-gear-fill"></i>
        </div>
        <div>
            <span>System Configuration</span>
            <h2>System Settings</h2>
            <p>Branding, outgoing mail, and your account security — all in one place.</p>
        </div>
    </div>

    <section class="content-panel settings-panel">
        <div class="panel-header">
            <div>
                <h2><i class="bi bi-image-fill me-2 text-primary"></i>Branding</h2>
                <p>The system name and logo shown across the Super Admin and Branch panels.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="admin-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="site_name" class="form-label">System Name</label>
                    <input id="site_name" type="text" name="site_name" value="{{ old('site_name', $settings->site_name) }}" class="form-control @error('site_name') is-invalid @enderror" placeholder="{{ config('app.name') }}">
                    @error('site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Shown in the sidebar for the Super Admin and Branch panels.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">System Logo</label>
                    <div class="logo-upload-zone">
                        <div class="logo-upload-preview">
                            <img src="{{ $settings->logo_path ? Storage::url($settings->logo_path) : '' }}" alt="Logo preview" data-logo-preview class="{{ $settings->logo_path ? '' : 'd-none' }}">
                            <div data-logo-placeholder @class(['logo-upload-placeholder', 'd-none' => $settings->logo_path])>
                                <i class="bi bi-image"></i>
                                <span>No logo yet</span>
                            </div>
                        </div>
                        <div class="logo-upload-actions">
                            <label for="logo" class="btn btn-soft btn-sm" data-logo-label>
                                <i class="bi bi-upload"></i>
                                {{ $settings->logo_path ? 'Change Logo' : 'Upload Logo' }}
                            </label>
                            <span class="form-text">PNG, JPG, or SVG. Max 2MB. Leave blank to keep the current logo.</span>
                        </div>
                        <input id="logo" type="file" name="logo" accept="image/*" class="d-none @error('logo') is-invalid @enderror" data-logo-input>
                    </div>
                    @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-circle-fill"></i>
                    Save Branding
                </button>
            </div>
        </form>
    </section>

    <section class="content-panel settings-panel">
        <div class="panel-header">
            <div>
                <h2><i class="bi bi-envelope-paper-fill me-2 text-primary"></i>SMTP / Mail Settings</h2>
                <p>Outgoing mail configuration used for password resets and notifications.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="admin-form">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <label for="mail_host" class="form-label">SMTP Host</label>
                    <input id="mail_host" type="text" name="mail_host" value="{{ old('mail_host', $settings->mail_host) }}" class="form-control @error('mail_host') is-invalid @enderror" placeholder="smtp.example.com">
                    @error('mail_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 col-lg-4">
                    <label for="mail_port" class="form-label">SMTP Port</label>
                    <input id="mail_port" type="number" min="1" max="65535" name="mail_port" value="{{ old('mail_port', $settings->mail_port) }}" class="form-control @error('mail_port') is-invalid @enderror" placeholder="587">
                    @error('mail_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 col-lg-4">
                    <label for="mail_username" class="form-label">SMTP Username</label>
                    <input id="mail_username" type="text" name="mail_username" value="{{ old('mail_username', $settings->mail_username) }}" class="form-control @error('mail_username') is-invalid @enderror" autocomplete="off">
                    @error('mail_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 col-lg-4">
                    <label for="mail_password" class="form-label">SMTP Password</label>
                    <div class="password-field">
                        <input id="mail_password" type="password" name="mail_password" class="form-control @error('mail_password') is-invalid @enderror" autocomplete="new-password" placeholder="{{ $settings->mail_password ? 'Leave blank to keep current password' : '' }}">
                        <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                    @error('mail_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 col-lg-4">
                    <label for="mail_encryption" class="form-label">Encryption</label>
                    <select id="mail_encryption" name="mail_encryption" class="form-select form-control @error('mail_encryption') is-invalid @enderror">
                        <option value="" @selected(old('mail_encryption', $settings->mail_encryption) === null)>None</option>
                        <option value="tls" @selected(old('mail_encryption', $settings->mail_encryption) === 'tls')>TLS</option>
                        <option value="ssl" @selected(old('mail_encryption', $settings->mail_encryption) === 'ssl')>SSL</option>
                    </select>
                    @error('mail_encryption')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 col-lg-4">
                    <label for="mail_mailer" class="form-label">Mailer</label>
                    <select id="mail_mailer" name="mail_mailer" class="form-select form-control @error('mail_mailer') is-invalid @enderror">
                        <option value="smtp" @selected(old('mail_mailer', $settings->mail_mailer ?: 'smtp') === 'smtp')>SMTP</option>
                        <option value="sendmail" @selected(old('mail_mailer', $settings->mail_mailer) === 'sendmail')>Sendmail</option>
                        <option value="log" @selected(old('mail_mailer', $settings->mail_mailer) === 'log')>Log (testing only)</option>
                    </select>
                    @error('mail_mailer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 col-lg-4">
                    <label for="mail_from_address" class="form-label">From Address</label>
                    <input id="mail_from_address" type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings->mail_from_address) }}" class="form-control @error('mail_from_address') is-invalid @enderror" placeholder="noreply@example.com">
                    @error('mail_from_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 col-lg-4">
                    <label for="mail_from_name" class="form-label">From Name</label>
                    <input id="mail_from_name" type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings->mail_from_name) }}" class="form-control @error('mail_from_name') is-invalid @enderror" placeholder="{{ config('app.name') }}">
                    @error('mail_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-circle-fill"></i>
                    Save Mail Settings
                </button>
            </div>
        </form>
    </section>

    <section class="content-panel settings-panel">
        <div class="panel-header">
            <div>
                <h2><i class="bi bi-envelope-at-fill me-2 text-primary"></i>Super Admin Email</h2>
                <p>Update the email address used to sign in and receive account notifications.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.account.email.update') }}" class="admin-form">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="form-control @error('email') is-invalid @enderror" required maxlength="255">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="account_current_password" class="form-label">Current Password</label>
                    <div class="password-field">
                        <input id="account_current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                    @error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text">Enter your current password to confirm this change.</div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-circle-fill"></i>
                    Save Email
                </button>
            </div>
        </form>
    </section>

    <section class="content-panel settings-panel">
        <div class="panel-header">
            <div>
                <h2><i class="bi bi-shield-lock-fill me-2 text-primary"></i>Change Password</h2>
                <p>Replace your current Super Admin password with a new secure one.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.password.update') }}" class="admin-form">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="current_password" class="form-label">Current Password</label>
                    <div class="password-field">
                        <input id="current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                    @error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label for="password" class="form-label">New Password</label>
                    <div class="password-field">
                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle-fill"></i>
                    Save Password
                </button>
            </div>
        </form>
    </section>

    @push('scripts')
        <script>
            document.querySelector('[data-logo-input]')?.addEventListener('change', function (event) {
                const file = event.target.files?.[0];
                if (!file) {
                    return;
                }

                const previewImg = document.querySelector('[data-logo-preview]');
                const placeholder = document.querySelector('[data-logo-placeholder]');
                const label = document.querySelector('[data-logo-label]');

                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('d-none');
                    placeholder?.classList.add('d-none');
                    if (label) {
                        label.innerHTML = '<i class="bi bi-upload"></i> Change Logo';
                    }
                };
                reader.readAsDataURL(file);
            });
        </script>
    @endpush
@endsection
