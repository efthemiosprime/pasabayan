<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration determines what cross-origin operations may execute
    | in web browsers. Adjust these settings as needed for security.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Apply CORS to all API endpoints

    'allowed_methods' => ['*'], // Allow all HTTP methods (GET, POST, PUT, DELETE, OPTIONS)

    'allowed_origins' => ['https://pasabayan.com', 'https://www.pasabayan.com'], // Allow frontend domain

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'Origin'], // ✅ Ensure Content-Type is allowed

    'exposed_headers' => ['Authorization'], // ✅ Expose Authorization headers if needed

    'max_age' => 0,

    'supports_credentials' => true, // ✅ Set to true if using authentication cookies

];