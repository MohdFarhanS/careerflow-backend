<?php

// Auth proyek ini Bearer-token (header Authorization), bukan cookie — jadi CORS tidak
// bergantung pada credentials. `supports_credentials` dibiarkan true agar tidak ada regresi
// pada deployment yang sudah jalan; `allowed_origins` digerakkan env (FRONTEND_URL).
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL', 'http://localhost:5173'),
        env('FRONTEND_URL_LOCAL', 'http://localhost:3000'),
    ])),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
