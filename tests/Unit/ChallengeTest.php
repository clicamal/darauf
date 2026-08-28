<?php

declare(strict_types=1);

use Clicamal\Darauf\Exceptions\ChallengeNotFoundException;
use Clicamal\Darauf\Services\Challenge;

it('generates a challenge with an id and a string', function () {
    $challenge = Challenge::generate('public-key');

    expect($challenge)->toHaveKeys(['challengeId', 'challengeString'])
        ->and(Cache::has("darauf_rsa_challenge:{$challenge['challengeId']}"))->toBeTrue();
});

it('verifies a valid signature', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $publicKey = openssl_pkey_get_details($key)['key'];

    $challenge = Challenge::generate($publicKey);

    openssl_sign($challenge['challengeString'], $signature, $key);

    expect(Challenge::verify($challenge['challengeId'], $signature))->toBe(1);
});

it('rejects an invalid signature', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $publicKey = openssl_pkey_get_details($key)['key'];

    $challenge = Challenge::generate($publicKey);

    openssl_sign('not-the-challenge', $signature, $key);

    expect(Challenge::verify($challenge['challengeId'], $signature))->toBe(0);
});

it('throws when the challenge is not found', function () {
    Challenge::verify('missing-challenge-id', 'signature');
})->throws(ChallengeNotFoundException::class);

it('is single use', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $publicKey = openssl_pkey_get_details($key)['key'];

    $challenge = Challenge::generate($publicKey);

    openssl_sign($challenge['challengeString'], $signature, $key);

    expect(Challenge::verify($challenge['challengeId'], $signature))->toBe(1);

    expect(fn () => Challenge::verify($challenge['challengeId'], $signature))
        ->toThrow(ChallengeNotFoundException::class);
});