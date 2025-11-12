<?php

return [
    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'directives' => [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],
            'form-action' => ["'self'"],
            'script-src' => [
                "'self'",
                "'nonce-{nonce}'",
            ],
            'script-src-attr' => ["'none'"],
            'style-src' => [
                "'self'",
                "'unsafe-inline'",
                "'nonce-{nonce}'",
                'https://fonts.googleapis.com',
                'https://fonts.bunny.net',
            ],
            'font-src' => [
                "'self'",
                'https://fonts.gstatic.com',
                'https://fonts.bunny.net',
                'data:',
            ],
            'img-src' => [
                "'self'",
                'data:',
                'https:',
            ],
            'connect-src' => [
                "'self'",
                'https:',
                'wss:',
            ],
            'media-src' => [
                "'self'",
            ],
            'manifest-src' => [
                "'self'",
            ],
        ],
    ],

    'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', "camera=(), microphone=(), geolocation=(), payment=(), usb=()"),
];
