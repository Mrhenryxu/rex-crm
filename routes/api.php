<?php

use App\Features\Contacts\Http\Controllers\ContactCallController;
use App\Features\Contacts\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:contacts-read')->group(function (): void {
        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/{contact}', [ContactController::class, 'show'])->whereUuid('contact')->name('contacts.show');
    });

    Route::middleware('throttle:contacts-write')->group(function (): void {
        Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
        Route::put('contacts/{contact}', [ContactController::class, 'update'])->whereUuid('contact')->name('contacts.update');
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->whereUuid('contact')->name('contacts.destroy');
    });

    Route::post('contacts/{contact}/calls', [ContactCallController::class, 'store'])
        ->whereUuid('contact')
        ->middleware('throttle:contacts-calls')
        ->name('contacts.calls.store');
});
