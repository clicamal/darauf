<?php

declare(strict_types=1);

use Clicamal\Darauf\ChallengeManagers\Ed25519\Ed25519ChallengeManager;
use Illuminate\Validation\ValidationException;

it('validates a generate challenge request', function () {
    $request = [
        'didDocumentId' => 'did:darauf:alice',
    ];

    expect(app(Ed25519ChallengeManager::class)->getGenerateChallengeRequestValidator($request)->validated())
        ->toBe($request);
});

it('rejects a generate challenge request without a did document id', function () {
    app(Ed25519ChallengeManager::class)->getGenerateChallengeRequestValidator([])->validate();
})->throws(ValidationException::class);

it('rejects a generate challenge request with a non-string did document id', function () {
    app(Ed25519ChallengeManager::class)->getGenerateChallengeRequestValidator(['didDocumentId' => 123])->validate();
})->throws(ValidationException::class);

it('validates a verify challenge request', function () {
    $request = [
        'challengeId' => 'challenge-id',
        'signature' => 'signature',
    ];

    expect(app(Ed25519ChallengeManager::class)->getValidateChallengeRequestValidator($request)->validated())
        ->toBe($request);
});

it('rejects a verify challenge request without a challenge id', function () {
    app(Ed25519ChallengeManager::class)->getValidateChallengeRequestValidator(['signature' => 'signature'])->validate();
})->throws(ValidationException::class);

it('rejects a verify challenge request without a signature', function () {
    app(Ed25519ChallengeManager::class)->getValidateChallengeRequestValidator(['challengeId' => 'challenge-id'])->validate();
})->throws(ValidationException::class);
