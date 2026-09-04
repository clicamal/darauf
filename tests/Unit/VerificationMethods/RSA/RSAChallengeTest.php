<?php

declare(strict_types=1);

use Clicamal\Darauf\Exceptions\DidDocumentNotFoundException;
use Clicamal\Darauf\Exceptions\InvalidDidException;
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

function unitRsaUser(string $suffix): array
{
    $did = 'did:darauf:'.$suffix;

    $document = DidDocument::factory()->create([
        'did_document_id' => $did,
    ]);

    $keyPair = rsaKeyPair();

    VerificationMethod::factory()->create([
        'verification_method_id' => $did.'#key-1',
        'did_document_id' => $document->id,
        'serialized' => json_encode([
            'id' => $did.'#key-1',
            'type' => 'RSA',
            'controller' => $did,
            'publicKeyMultibase' => $keyPair['publicKeyMultibase'],
        ]),
    ]);

    return [
        'did' => $did,
        'private' => $keyPair['private'],
        'publicKeyMultibase' => $keyPair['publicKeyMultibase'],
    ];
}

it('generates a challenge with an id and a string', function () {
    $user = unitRsaUser('alice');

    $challenge = RSA::generateChallenge(['didDocumentId' => $user['did']]);

    expect($challenge)->toHaveKeys(['id', 'string'])
        ->and(Cache::has("darauf_rsa_challenge:{$challenge['id']}"))->toBeTrue();
});

it('throws when the did is not valid', function () {
    RSA::generateChallenge(['didDocumentId' => 'not-a-did']);
})->throws(InvalidDidException::class);

it('throws when the did document does not exist', function () {
    RSA::generateChallenge(['didDocumentId' => 'did:darauf:ghost']);
})->throws(DidDocumentNotFoundException::class);

it('throws when the did document has no rsa verification method', function () {
    $document = DidDocument::factory()->create([
        'did_document_id' => 'did:darauf:nosuchkey',
    ]);

    VerificationMethod::factory()->create([
        'verification_method_id' => 'did:darauf:nosuchkey#key-1',
        'did_document_id' => $document->id,
        'serialized' => json_encode([
            'id' => 'did:darauf:nosuchkey#key-1',
            'type' => 'Ed25519',
            'controller' => 'did:darauf:nosuchkey',
            'publicKeyMultibase' => 'z'.base64url_encode('some-key'),
        ]),
    ]);

    RSA::generateChallenge(['didDocumentId' => 'did:darauf:nosuchkey']);
})->throws(ChallengeNotFoundException::class);

it('verifies a valid signature', function () {
    $user = unitRsaUser('alice');

    $challenge = RSA::generateChallenge(['didDocumentId' => $user['did']]);

    openssl_sign($challenge['string'], $signature, $user['private']);

    expect(RSA::verifyChallenge([
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ]))->toBeTrue();
});

it('rejects an invalid signature', function () {
    $user = unitRsaUser('alice');

    $challenge = RSA::generateChallenge(['didDocumentId' => $user['did']]);

    openssl_sign('not-the-challenge', $signature, $user['private']);

    expect(RSA::verifyChallenge([
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ]))->toBeFalse();
});

it('throws when the challenge is not found', function () {
    RSA::verifyChallenge([
        'challengeId' => 'missing-challenge-id',
        'signature' => base64_encode('signature'),
    ]);
})->throws(ChallengeNotFoundException::class);

it('is single use', function () {
    $user = unitRsaUser('alice');

    $challenge = RSA::generateChallenge(['didDocumentId' => $user['did']]);

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
