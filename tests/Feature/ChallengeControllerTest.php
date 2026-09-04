<?php

declare(strict_types=1);

use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();
});

function rsaUser(string $suffix): array
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

it('returns a challenge for a registered document', function () {
    $user = rsaUser('alice');

    $response = $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'didDocumentId' => $user['did'],
    ]);

    $response->assertCreated();

    $challenge = $response->json();

    expect($challenge)->toHaveKeys(['id', 'string'])
        ->and(Cache::has("darauf_rsa_challenge:{$challenge['id']}"))->toBeTrue();
});

it('rejects a challenge for an unregistered document', function () {
    $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'didDocumentId' => 'did:darauf:ghost',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.did_document_not_found'));
});

it('rejects a challenge for an invalid did', function () {
    $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'didDocumentId' => 'not-a-did',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.invalid_did'));
});

it('rejects a did document id without an rsa verification method', function () {
    rsaUser('alice');

    $document = DidDocument::factory()->create([
        'did_document_id' => 'did:darauf:norsa',
    ]);

    VerificationMethod::factory()->create([
        'verification_method_id' => 'did:darauf:norsa#key-1',
        'did_document_id' => $document->id,
        'serialized' => json_encode([
            'id' => 'did:darauf:norsa#key-1',
            'type' => 'Ed25519',
            'controller' => 'did:darauf:norsa',
            'publicKeyMultibase' => 'z'.base64url_encode('some-key'),
        ]),
    ]);

    $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'didDocumentId' => 'did:darauf:norsa',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::verification_methods.rsa.challenge_not_found'));
});

it('rejects a missing didDocumentId in the generate endpoint', function () {
    $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['didDocumentId']);
});

it('registers the named challenge generate route', function () {
    expect(route('darauf.verification.challenge.generate', ['method' => 'RSA']))
        ->toBe('http://localhost/api/darauf/v0.1.0/challenge/generate/RSA');
});

it('accepts a valid signature in the verify endpoint', function () {
    $user = rsaUser('alice');

    $challengeResponse = $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'didDocumentId' => $user['did'],
    ])->assertCreated();

    $challenge = $challengeResponse->json();

    openssl_sign($challenge['string'], $signature, $user['private']);

    $this->postJson(route('darauf.verification.challenge.verify', ['method' => 'RSA']), [
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ])->assertOk()
        ->assertJsonPath('message', __('darauf::messages.success.did_subject_authenticated'));
});

it('rejects an invalid signature in the verify endpoint', function () {
    $user = rsaUser('alice');

    $challengeResponse = $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'didDocumentId' => $user['did'],
    ])->assertCreated();

    $challenge = $challengeResponse->json();

    openssl_sign('not-the-challenge', $signature, $user['private']);

    $this->postJson(route('darauf.verification.challenge.verify', ['method' => 'RSA']), [
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.verification_failed'));
});

it('rejects a challenge that was never generated', function () {
    $this->postJson(route('darauf.verification.challenge.verify', ['method' => 'RSA']), [
        'challengeId' => 'missing-challenge-id',
        'signature' => base64_encode('signature'),
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::verification_methods.rsa.challenge_not_found'));
});

it('rejects a missing payload in the verify endpoint', function () {
    $this->postJson(route('darauf.verification.challenge.verify', ['method' => 'RSA']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['challengeId', 'signature']);
});

it('registers the named verify route', function () {
    expect(route('darauf.verification.challenge.verify', ['method' => 'RSA']))
        ->toBe('http://localhost/api/darauf/v0.1.0/challenge/verify/RSA');
});
