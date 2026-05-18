<?php

return [

    // Otwarte tylko endpointy API zapytań ofertowych — żeby widget z domeny klienta SaaS
    // mógł wysłać POST na nasz serwer bez błędu CORS.
    'paths' => ['api/*'],

    'allowed_methods' => ['POST', 'GET', 'OPTIONS'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
