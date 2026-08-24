@include('errors.partials.error-page', [
    'code' => 404,
    'title' => 'Page Not Found',
    'message' => 'The page you are looking for does not exist or may have been moved.',
    'icon' => 'bi-compass',
    'iconClass' => 'compass',
])
