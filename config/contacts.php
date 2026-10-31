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

    /*
    |--------------------------------------------------------------------------
    | Read caching
    |--------------------------------------------------------------------------
    |
    | Uses the configured cache store (database by default). Caches contact
    | details (with latest calls) and unfiltered listing pages only. Filtered
    | searches are not cached because their parameters rarely repeat.
    |
    */

    'cache' => [
        'enabled' => (bool) env('CONTACTS_CACHE_ENABLED', true),
        'detail_ttl' => (int) env('CONTACTS_CACHE_DETAIL_TTL', 60),
        'list_ttl' => (int) env('CONTACTS_CACHE_LIST_TTL', 30),
    ],

    'rate_limits' => [
        'read' => (int) env('CONTACTS_RATE_LIMIT_READ', 120),
        'write' => (int) env('CONTACTS_RATE_LIMIT_WRITE', 30),
        'calls' => (int) env('CONTACTS_RATE_LIMIT_CALLS', 10),
    ],

];
