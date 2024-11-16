<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS
    |--------------------------------------------------------------------------
    |
    | The settings for handling Cross-Origin Resource Sharing (CORS) requests.
    | This file contains the necessary configuration to allow or restrict
    | incoming requests based on various criteria, including methods,
    | origins, and headers.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'], // Можете указать конкретные методы, например, ['GET', 'POST', 'PUT', 'DELETE']

    'allowed_origins' => ['http://localhost:3000'], // Адрес вашего фронтенда (например, http://localhost:3000 для Next.js)

    'allowed_headers' => ['*'], // Заголовки, которые разрешено отправлять

    'exposed_headers' => ['Authorization'], // Заголовки, которые могут быть доступны на клиенте

    'max_age' => 3600, // Время кэширования CORS в секундах

    'supports_credentials' => true, // Разрешить отправку cookies или других данных аутентификации
];
