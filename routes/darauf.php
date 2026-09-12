<?php

declare(strict_types=1);

use Clicamal\Darauf\Http\Controllers\ChallengeController;
use Clicamal\Darauf\Http\Controllers\DidDocumentController;
use Illuminate\Support\Facades\Route;

// Route::get('darauf', fn () => 'Darauf placeholder route.')->name('darauf.placeholder');

Route::prefix('api/darauf/v0.1.4')
    ->middleware('api')
    ->group(function () {
        Route::post('diddocuments', [DidDocumentController::class, 'register'])->name('darauf.diddocuments.register');

        Route::post('challenge/generate', [ChallengeController::class, 'generate'])->name('darauf.challenge.generate');

        Route::post('challenge/verify', [ChallengeController::class, 'verify'])->name('darauf.challenge.verify');
    });
