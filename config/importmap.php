<?php

// config/importmap.php

return [
    'app' => [
        'path' => 'assets/app.js',
        'preload' => true,
    ],
    'bootstrap' => [
        'url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
        'preload' => true,
    ],
    'jquery' => [
        'url' => 'https://code.jquery.com/jquery-3.6.0.min.js',
        'preload' => true,
    ],
    'controllers' => [
        'path' => 'controllers.js',
        'preload' => true,
    ],
];
