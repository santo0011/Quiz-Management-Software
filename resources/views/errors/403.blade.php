@php
    $customMessage = $exception?->getMessage();
    $hasCustomMessage = filled($customMessage) && $customMessage !== 'This action is unauthorized.';
@endphp
@include('errors.partials.error-page', [
    'code' => 403,
    'title' => 'Access Denied',
    'message' => $hasCustomMessage ? $customMessage : 'You do not have permission to access this page. Please contact your administrator if you believe this is a mistake.',
    'icon' => 'bi-shield-lock-fill',
    'iconClass' => 'warning',
])