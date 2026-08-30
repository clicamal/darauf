<?php

declare(strict_types=1);

use Clicamal\Darauf\Http\Controllers\DidDocumentController;
use Clicamal\Darauf\Http\Controllers\RsaVerification\RsaVerificationController;
use Illuminate\Support\Facades\Route;

// Route::get('darauf', fn () => 'Darauf placeholder route.')->name('darauf.placeholder');

Route::prefix('api/darauf/v0.1.0')
    ->middleware('api')
    ->group(function () {
        Route::post('diddocuments', [DidDocumentController::class, 'create'])->name('darauf.diddocuments.create');
        Route::post('/verification/rsa/challenge', [RsaVerificationController::class, 'generateChallenge'])->name('darauf.verification.rsa.challenge.generate');
        Route::post('/verification/rsa/verify', [RsaVerificationController::class, 'verify'])->name('darauf.verification.rsa.challenge.verify');
    });
