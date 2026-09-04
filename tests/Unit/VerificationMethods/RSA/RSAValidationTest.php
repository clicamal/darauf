<?php

declare(strict_types=1);

use Clicamal\Darauf\VerificationMethods\RSA\RSA;
use Illuminate\Validation\ValidationException;

it('validates a generate challenge request', function () {
    $request = [
        'didDocumentId' => 'did:darauf:alice',
    ];

    expect(RSA::validateGenerateChallengeRequest($request))->toBe($request);
});

it('rejects a generate challenge request without a did document id', function () {
    RSA::validateGenerateChallengeRequest([]);
})->throws(ValidationException::class);

it('rejects a generate challenge request with a non-string did document id', function () {
    RSA::validateGenerateChallengeRequest(['didDocumentId' => 123]);
})->throws(ValidationException::class);

it('validates a verify challenge request', function () {
    $request = [
        'challengeId' => 'challenge-id',
        'signature' => 'signature',
    ];

    expect(RSA::validateVerifyChallengeRequest($request))->toBe($request);
});

it('rejects a verify challenge request without a challenge id', function () {
    RSA::validateVerifyChallengeRequest(['signature' => 'signature']);
})->throws(ValidationException::class);

it('rejects a verify challenge request without a signature', function () {
    RSA::validateVerifyChallengeRequest(['challengeId' => 'challenge-id']);
})->throws(ValidationException::class);
