@include('auth.passwords.partials.shell', [
    'title' => $config['title'],
    'heading' => $config['heading'],
    'copy' => $config['copy'],
    'slot' => 'auth.passwords.partials.email-form',
])