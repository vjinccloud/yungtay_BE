<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'admin/*',           // 後台路由
        'member/*',          // 會員相關 AJAX 路由
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        'https://thc-vue.vercel.app',
        env('APP_URL'), // 自動使用 .env 中的 APP_URL
        // 開發環境用
        env('APP_ENV') === 'local' ? 'http://localhost:5173' : null,
        env('APP_ENV') === 'local' ? 'http://127.0.0.1:5173' : null,
        env('APP_ENV') === 'local' ? 'http://[::1]:5173' : null,
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
