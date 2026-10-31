<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contacts listing pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_per_page' => (int) env('CONTACTS_DEFAULT_PER_PAGE', 15),
        'max_per_page' => (int) env('CONTACTS_MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | API rate limits (requests per minute, per client IP)
    |--------------------------------------------------------------------------
    |
    | Rate limiting protects the API from abuse. It is not authentication.
    |
    */

    'rate_limits' => [
        'read' => (int) env('CONTACTS_RATE_LIMIT_READ', 120),
        'write' => (int) env('CONTACTS_RATE_LIMIT_WRITE', 30),
        'calls' => (int) env('CONTACTS_RATE_LIMIT_CALLS', 10),
    ],

];
