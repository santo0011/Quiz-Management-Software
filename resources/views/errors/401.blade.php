@include('errors.partials.error-page', [
    'code' => 401,
    'title' => 'Unauthorized',
    'message' => 'You must be logged in to access this page. Please log in to continue.',
    'icon' => 'bi-person-lock-fill',
    'iconClass' => 'user',
])