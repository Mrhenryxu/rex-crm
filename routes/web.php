<?php

use Illuminate\Support\Facades\Route;

// React SPA: every non-API path renders the same shell; React Router handles the rest.
Route::view('/{path?}', 'app')
    ->where('path', '^(?!api(?:/|$)).*$')
    ->name('spa');
