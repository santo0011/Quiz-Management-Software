@extends('layouts.admin')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
    <section class="content-panel narrow-panel">
        <div class="panel-header">
            <div>
                <h2>System Settings</h2>
                <p>Branding, system information, and outgoing mail configuration.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="admin-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="exam-form-sections">
                <section class="exam-form-section">
                    <div class="exam-form-section-title">
                        <span>01</span>
                        <h3>Branding</h3>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="site_name" class="form-label">System Name</label>
                            <input id="site_name" type="text" name="site_name" value="{{ old('site_name', $settings->site_name) }}" class="form-control @error('site_name') is-invalid @enderror" placeholder="{{ config('app.name') }}">
                            @error('site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Shown in the sidebar for the Super Admin and Branch panels.</div>
                        </div>
                        <div class="col-12">
                            <label for="logo" class="form-label">System Logo</label>
                            @if ($settings->logo_path)
                                <div class="mb-2">
                                    <img src="{{ Storage::url($settings->logo_path) }}" alt="Current logo" style="max-height: 56px; max-width: 220px; object-fit: contain;">
                                </div>
                            @endif
                            <input id="logo" type="file" name="logo" accept="image/*" class="form-control @error('logo') is-invalid @enderror">
                            @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">PNG, JPG, or SVG. Max 2MB. Leave blank to keep the current logo.</div>
                        </div>
                    </div>
                </section>

                <section class="exam-form-section">
                    <div class="exam-form-section-title">
                        <span>02</span>
                        <h3>SMTP / Mail Settings</h3>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="mail_host" class="form-label">SMTP Host</label>
                            <input id="mail_host" type="text" name="mail_host" value="{{ old('mail_host', $settings->mail_host) }}" class="form-control @error('mail_host') is-invalid @enderror" placeholder="smtp.example.com">
                            @error('mail_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="mail_port" class="form-label">SMTP Port</label>
                            <input id="mail_port" type="number" min="1" max="65535" name="mail_port" value="{{ old('mail_port', $settings->mail_port) }}" class="form-control @error('mail_port') is-invalid @enderror" placeholder="587">
                            @error('mail_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="mail_username" class="form-label">SMTP Username</label>
                            <input id="mail_username" type="text" name="mail_username" value="{{ old('mail_username', $settings->mail_username) }}" class="form-control @error('mail_username') is-invalid @enderror" autocomplete="off">
                            @error('mail_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="mail_password" class="form-label">SMTP Password</label>
                            <input id="mail_password" type="password" name="mail_password" class="form-control @error('mail_password') is-invalid @enderror" autocomplete="new-password" placeholder="{{ $settings->mail_password ? 'Leave blank to keep current password' : '' }}">
                            @error('mail_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="mail_encryption" class="form-label">Encryption</label>
                            <select id="mail_encryption" name="mail_encryption" class="form-select form-control @error('mail_encryption') is-invalid @enderror">
                                <option value="" @selected(old('mail_encryption', $settings->mail_encryption) === null)>None</option>
                                <option value="tls" @selected(old('mail_encryption', $settings->mail_encryption) === 'tls')>TLS</option>
                                <option value="ssl" @selected(old('mail_encryption', $settings->mail_encryption) === 'ssl')>SSL</option>
                            </select>
                            @error('mail_encryption')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="mail_mailer" class="form-label">Mailer</label>
                            <select id="mail_mailer" name="mail_mailer" class="form-select form-control @error('mail_mailer') is-invalid @enderror">
                                <option value="smtp" @selected(old('mail_mailer', $settings->mail_mailer ?: 'smtp') === 'smtp')>SMTP</option>
                                <option value="sendmail" @selected(old('mail_mailer', $settings->mail_mailer) === 'sendmail')>Sendmail</option>
                                <option value="log" @selected(old('mail_mailer', $settings->mail_mailer) === 'log')>Log (testing only)</option>
                            </select>
                            @error('mail_mailer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="mail_from_address" class="form-label">From Address</label>
                            <input id="mail_from_address" type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings->mail_from_address) }}" class="form-control @error('mail_from_address') is-invalid @enderror" placeholder="noreply@example.com">
                            @error('mail_from_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="mail_from_name" class="form-label">From Name</label>
                            <input id="mail_from_name" type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings->mail_from_name) }}" class="form-control @error('mail_from_name') is-invalid @enderror" placeholder="{{ config('app.name') }}">
                            @error('mail_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-circle-fill"></i>
                    Save Settings
                </button>
            </div>
        </form>
    </section>
@endsection
