<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} - {{ $title }} | {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .error-body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 20px;
        }
        .error-card {
            max-width: 520px;
            width: 100%;
            text-align: center;
            background: #fff;
            border-radius: 16px;
            padding: 48px 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        .error-code {
            font-size: 96px;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #1e293b, #475569);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -2px;
        }
        .error-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 32px;
        }
        .error-icon.warning { background: #fef2f2; color: #dc2626; }
        .error-icon.lock { background: #f0f9ff; color: #0284c7; }
        .error-icon.timer { background: #fffbeb; color: #d97706; }
        .error-icon.server { background: #f8fafc; color: #64748b; }
        .error-icon.user { background: #eff6ff; color: #2563eb; }
        .error-icon.compass { background: #f0f9ff; color: #0284c7; }
        .error-card h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }
        .error-message {
            color: #64748b;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .error-actions .btn {
            padding: 10px 28px;
            font-weight: 600;
            border-radius: 8px;
            margin: 4px;
        }
        .error-actions .btn i {
            margin-right: 8px;
        }
        @media (max-width: 480px) {
            .error-card { padding: 36px 20px; }
            .error-code { font-size: 72px; }
        }
    </style>
</head>
<body>
    <div class="error-body">
        <div class="error-card">
            <div class="error-code">{{ $code }}</div>
            <div class="error-icon {{ $iconClass }}"><i class="bi {{ $icon }}"></i></div>
            <h1>{{ $title }}</h1>
            <p class="error-message">{{ $message }}</p>
            <div class="error-actions">
                @php
                    $currentUser = \App\Support\RoleRedirector::currentUser();
                @endphp
                @if($currentUser)
                    <a href="{{ \App\Support\RoleRedirector::dashboardUrl($currentUser) }}" class="btn btn-primary">
                        <i class="bi bi-speedometer2"></i>Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i>Go to Login
                    </a>
                @endif
            </div>
        </div>
    </div>
</body>
</html>