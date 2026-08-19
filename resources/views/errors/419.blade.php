@include('errors.partials.error-page', [
    'code' => 419,
    'title' => 'Session Expired',
    'message' => 'Your session has expired. Please log in again to continue.',
    'icon' => 'bi-hourglass-split',
    'iconClass' => 'timer',
])