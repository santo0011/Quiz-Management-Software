@include('errors.partials.error-page', [
    'code' => 403,
    'title' => 'Access Denied',
    'message' => 'You do not have permission to access this page. Please contact your administrator if you believe this is a mistake.',
    'icon' => 'bi-shield-lock-fill',
    'iconClass' => 'warning',
])