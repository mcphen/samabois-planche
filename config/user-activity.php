<?php

return [
    // Keys to redact from the request body before persisting
    'redact_keys' => [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        '_token',
        'remember',
        'authorization',
    ],

    // Maximum characters to store from the serialized request body
    'max_request_body' => env('USER_ACTIVITY_MAX_BODY', 2000),
];
