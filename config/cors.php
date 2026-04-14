<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Thay vì để dấu sao, hãy điền chính xác domain GitHub của bạn
    'allowed_origins' => ['https://duongdaica45.github.io'], 
    //'allowed_origins' => ['*'], 

    // Quan trọng: Để mảng rỗng nếu không dùng pattern
    'allowed_origins_patterns' => [], 

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];