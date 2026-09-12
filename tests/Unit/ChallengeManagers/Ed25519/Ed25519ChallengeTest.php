<?php

declare(strict_types=1);

use Clicamal\Darauf\ChallengeManagers\Ed25519\Ed25519ChallengeManager;
use Clicamal\Darauf\Exceptions\ChallengeManagementException;
use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\VerificationMethod;

function multibaseResource(string $multibase): VerificationMethod
{
    return new VerificationMethod([
        'verification_method_id' => 'did:darauf:alice#key-1',
        'did_document_id' => 1,
        'serialized' => json_encode([
            'id' => 'did:darauf:alice#key-1',
            'type' => 'Ed25519VerificationKey2020',
            'controller' => 'did:darauf:alice',
            'publicKeyMultibase' => $multibase,
        ]),
    ]);
}

function jwkResource(array $jwk): Authentication
{
    return new Authentication([
        'authentication_id' => 'did:darauf:alice#key-1',
        'did_document_id' => 1,
        'serialized' => json_encode([
            'id' => 'did:darauf:alice#key-1',
            'type' => 'JsonWebKey2020',
            'controller' => 'did:darauf:alice',
            'publicKeyJwk' => $jwk,
        ]),
    ]);
}

it('verifies a valid multibase ed25519 signature', function () {
    $keyPair = ed25519KeyPair();
    $nonce = 'challenge-nonce';

    $signature = sodium_crypto_sign_detached($nonce, $keyPair['private']);

    expect(app(Ed25519ChallengeManager::class)->verify(
        base64_encode($signature),
        $nonce,
        multibaseResource($keyPair['publicKeyMultibase']),
    ))->toBeTrue();
});

it('rejects an invalid multibase signature', function () {
    $keyPair = ed25519KeyPair();

    $signature = sodium_crypto_sign_detached('not-the-nonce', $keyPair['private']);

    expect(app(Ed25519ChallengeManager::class)->verify(
        base64_encode($signature),
        'the-real-nonce',
        multibaseResource($keyPair['publicKeyMultibase']),
    ))->toBeFalse();
});

it('verifies a valid ed25519 jwk signature', function () {
    $keyPair = ed25519KeyPair();
    $nonce = 'challenge-nonce';

    $signature = sodium_crypto_sign_detached($nonce, $keyPair['private']);

    expect(app(Ed25519ChallengeManager::class)->verify(
        base64_encode($signature),
        $nonce,
        jwkResource($keyPair['publicKeyJwk']),
    ))->toBeTrue();
});

it('rejects a non-ed25519 jwk', function () {
    app(Ed25519ChallengeManager::class)->verify(
        base64_encode('signature'),
        'nonce',
        jwkResource(['kty' => 'RSA', 'n' => 'abc', 'e' => 'AQAB']),
    );
})->throws(ChallengeManagementException::class);

it('rejects an invalid multibase key', function () {
    app(Ed25519ChallengeManager::class)->verify(
        base64_encode('signature'),
        'nonce',
        multibaseResource('z'.base58_encode('not-an-ed25519-key')),
    );
})->throws(ChallengeManagementException::class);

it('rejects a multibase key without a supported prefix', function () {
    app(Ed25519ChallengeManager::class)->verify(
        base64_encode('signature'),
        'nonce',
        multibaseResource('q'.str_repeat('a', 40)),
    );
})->throws(ChallengeManagementException::class);

it('rejects an empty signature', function () {
    $keyPair = ed25519KeyPair();

    expect(app(Ed25519ChallengeManager::class)->verify(
        '',
        'nonce',
        multibaseResource($keyPair['publicKeyMultibase']),
    ))->toBeFalse();
});

it('rejects a resource without a public key', function () {
    $resource = new VerificationMethod([
        'verification_method_id' => 'did:darauf:alice#key-1',
        'did_document_id' => 1,
        'serialized' => json_encode([
            'id' => 'did:darauf:alice#key-1',
            'type' => 'Ed25519VerificationKey2020',
            'controller' => 'did:darauf:alice',
        ]),
    ]);

    app(Ed25519ChallengeManager::class)->verify(
        base64_encode('signature'),
        'nonce',
        $resource,
    );
})->throws(ChallengeManagementException::class);
