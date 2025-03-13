<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration determines what cross-origin operations may execute
    | in web browsers. You can adjust these settings as needed for security.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Defines where CORS applies

    'allowed_methods' => ['*'], // Allow all HTTP methods (GET, POST, PUT, DELETE, etc.)

    'allowed_origins' => ['https://pasabayan.com'], // Replace with your frontend domain

    'allowed_origins_patterns' => [], // Wildcard patterns for allowed origins

    'allowed_headers' => ['*'], // Allow all headers

    'exposed_headers' => [], // Headers that can be exposed

    'max_age' => 0, // Caching time for preflight requests

    'supports_credentials' => false, // Set to true if using cookies or authentication tokens

];
