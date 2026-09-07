<?php

declare(strict_types=1);

use Clicamal\Darauf\ChallengeManagers\Ed25519\Ed25519ChallengeManager;
use Clicamal\Darauf\ChallengeManagers\Ed25519\Exceptions\VerificationMethodNotFoundException;
use Clicamal\Darauf\Exceptions\ChallengeNotFoundException;
use Clicamal\Darauf\Exceptions\DidDocumentNotFoundException;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();
});

function unitEd25519User(string $suffix): array
{
    $did = 'did:darauf:'.$suffix;

    $document = DidDocument::factory()->create([
        'did_document_id' => $did,
    ]);

    $keyPair = ed25519KeyPair();

    VerificationMethod::factory()->create([
        'verification_method_id' => $did.'#key-1',
        'did_document_id' => $document->id,
        'serialized' => json_encode([
            'id' => $did.'#key-1',
            'type' => 'Ed25519VerificationKey2020',
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
    $user = unitEd25519User('alice');

    $challenge = app(Ed25519ChallengeManager::class)->generateChallenge(['didDocumentId' => $user['did']]);

    expect($challenge)->toHaveKeys(['id', 'string'])
        ->and(Cache::has("darauf_ed25519_challenge:{$challenge['id']}"))->toBeTrue();
});

it('throws when the did document does not exist', function () {
    app(Ed25519ChallengeManager::class)->generateChallenge(['didDocumentId' => 'did:darauf:ghost']);
})->throws(DidDocumentNotFoundException::class);

it('throws when the did document has no ed25519 verification method', function () {
    $document = DidDocument::factory()->create([
        'did_document_id' => 'did:darauf:nosuchkey',
    ]);

    VerificationMethod::factory()->create([
        'verification_method_id' => 'did:darauf:nosuchkey#key-1',
        'did_document_id' => $document->id,
        'serialized' => json_encode([
            'id' => 'did:darauf:nosuchkey#key-1',
            'type' => 'RSA',
            'controller' => 'did:darauf:nosuchkey',
            'publicKeyMultibase' => 'z'.base64url_encode('some-key'),
        ]),
    ]);

    app(Ed25519ChallengeManager::class)->generateChallenge(['didDocumentId' => 'did:darauf:nosuchkey']);
})->throws(VerificationMethodNotFoundException::class);

it('verifies a valid ed25519 signature', function () {
    $user = unitEd25519User('alice');

    $challenge = app(Ed25519ChallengeManager::class)->generateChallenge(['didDocumentId' => $user['did']]);

    $signature = sodium_crypto_sign_detached($challenge['string'], $user['private']);

    expect(app(Ed25519ChallengeManager::class)->verifyChallenge([
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ]))->toBeTrue();
});

it('rejects an invalid signature', function () {
    $user = unitEd25519User('alice');

    $challenge = app(Ed25519ChallengeManager::class)->generateChallenge(['didDocumentId' => $user['did']]);

    $signature = sodium_crypto_sign_detached('not-the-challenge', $user['private']);

    expect(app(Ed25519ChallengeManager::class)->verifyChallenge([
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ]))->toBeFalse();
});

it('throws when the challenge is not found', function () {
    app(Ed25519ChallengeManager::class)->verifyChallenge([
        'challengeId' => 'missing-challenge-id',
        'signature' => base64_encode('signature'),
    ]);
})->throws(ChallengeNotFoundException::class);

it('is single use', function () {
    $user = unitEd25519User('alice');

    $challenge = app(Ed25519ChallengeManager::class)->generateChallenge(['didDocumentId' => $user['did']]);

    $signature = sodium_crypto_sign_detached($challenge['string'], $user['private']);

    expect(app(Ed25519ChallengeManager::class)->verifyChallenge([
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ]))->toBeTrue();

    expect(fn () => app(Ed25519ChallengeManager::class)->verifyChallenge([
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ]))->toThrow(ChallengeNotFoundException::class);
});
