<?php

declare(strict_types=1);

use Clicamal\Darauf\VerificationMethods\RSA\RSA;
use Illuminate\Validation\ValidationException;

it('returns the verification type', function () {
    expect(RSA::getVerificationType())->toBe('RSA');
});

it('validates a generate challenge request', function () {
    $request = [
        'username' => 'alice',
    ];

    expect(RSA::validateGenerateChallengeRequest($request))->toBe($request);
});

it('rejects a generate challenge request without a username', function () {
    RSA::validateGenerateChallengeRequest([]);
})->throws(ValidationException::class);

it('validates a verify challenge request', function () {
    $request = [
        'challengeId' => 'challenge-id',
        'signature' => 'signature',
    ];

    expect(RSA::validateVerifyChallengeRequest($request))->toBe($request);
});
