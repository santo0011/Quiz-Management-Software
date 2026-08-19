@include('errors.partials.error-page', [
    'code' => 500,
    'title' => 'Server Error',
    'message' => 'Something went wrong on our end. Please try again later. If the problem persists, contact support.',
    'icon' => 'bi-exclamation-triangle-fill',
    'iconClass' => 'server',
])