<?php

use App\Models\Admin;
use App\Models\StoreUser;

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'store_users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'store_users',
        ],
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'store_users' => [
            'driver' => 'eloquent',
            'model' => StoreUser::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],
    ],

    'passwords' => [
        'store_users' => [
            'provider' => 'store_users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
