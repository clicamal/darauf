<?php

declare(strict_types=1);

use Clicamal\Darauf\Exceptions\DidDocumentNotFoundException;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
use Clicamal\Darauf\VerificationMethods\RSA\Exceptions\ChallengeNotFoundException;
use Clicamal\Darauf\VerificationMethods\RSA\RSA;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();
});

function unitRsaUser(string $username): array
{
    $did = DidDocument::generateSha256DidFromUsername($username);

    DidDocument::create(['did' => $did]);

    $key = openssl_pkey_new(['private_key_bits' => 2048]);

    $publicPem = openssl_pkey_get_details($key)['key'];

    $der = base64_decode(str_replace(["\n", "\r", '-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----'], '', $publicPem));

    $multibase = 'u'.rtrim(strtr(base64_encode($der), '+/', '-_'), '=');

    VerificationMethod::create([
        'id' => $did.'#key1',
        'did_document_did' => $did,
        'controller' => $did,
        'type' => 'RSA',
        'publicKeyMultibase' => $multibase,
    ]);

    return [
        'did' => $did,
        'private' => $key,
        'publicMultibase' => $multibase,
    ];
}

it('generates a challenge with an id and a string', function () {
    unitRsaUser('alice');

    $challenge = RSA::generateChallenge(['username' => 'alice']);

    expect($challenge)->toHaveKeys(['id', 'string'])
        ->and(Cache::has("darauf_rsa_challenge:{$challenge['id']}"))->toBeTrue();
});

it('throws when the did document does not exist', function () {
    RSA::generateChallenge(['username' => 'ghost']);
})->throws(DidDocumentNotFoundException::class);

it('throws when the did document has no rsa verification method', function () {
    DidDocument::create(['did' => DidDocument::generateSha256DidFromUsername('alice')]);

    RSA::generateChallenge(['username' => 'alice']);
})->throws(ChallengeNotFoundException::class);

it('verifies a valid signature', function () {
    $user = unitRsaUser('alice');

    $challenge = RSA::generateChallenge(['username' => 'alice']);

    openssl_sign($challenge['string'], $signature, $user['private']);

    expect(RSA::verifyChallenge([
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ]))->toBeTrue();
});

it('rejects an invalid signature', function () {
    $user = unitRsaUser('alice');

    $challenge = RSA::generateChallenge(['username' => 'alice']);

    openssl_sign('not-the-challenge', $signature, $user['private']);

    expect(RSA::verifyChallenge([
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ]))->toBeFalse();
});

it('throws when the challenge is not found', function () {
    RSA::verifyChallenge([
        'challengeId' => 'missing-challenge-id',
        'signature' => 'signature',
    ]);
})->throws(ChallengeNotFoundException::class);

it('is single use', function () {
    $user = unitRsaUser('alice');

    $challenge = RSA::generateChallenge(['username' => 'alice']);

    openssl_sign($challenge['string'], $signature, $user['private']);

    expect(RSA::verifyChallenge([
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ]))->toBeTrue();

    expect(fn () => RSA::verifyChallenge([
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ]))->toThrow(ChallengeNotFoundException::class);
});
