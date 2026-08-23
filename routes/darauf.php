<?php

declare(strict_types=1);

use Clicamal\Darauf\Http\Controllers\DidDocumentController;
use Illuminate\Support\Facades\Route;

// Route::get('darauf', fn () => 'Darauf placeholder route.')->name('darauf.placeholder');

Route::prefix('api/darauf/v1')
    ->middleware('api')
    ->group(function () {
        Route::post('diddocuments', [DidDocumentController::class, 'create'])->name('darauf.diddocuments.create');
    });
