<?php

return [
    'url' => env('API_PRESTASHOP_ENDPOINT', 'http://domain.com'),
    'token' => env('API_PRESTASHOP_TOKEN', ''),
    'debug' => env('PRESTASHOP_DEBUG', env('APP_DEBUG', false))
];
