<?php

declare(strict_types=1);

use Clicamal\Darauf\Did\DidDomainModel;
use Clicamal\Darauf\Did\DidResolverContract;
use Clicamal\Darauf\Did\DidUrlDomainModel;
use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;

class DaraufTestResolver implements DidResolverContract
{
    public function resolve(DidDomainModel $did): ?DidDocument
    {
        return DidDocument::where('did_document_id', $did->getFull())->first();
    }

    public function dereference(DidUrlDomainModel $didUrl): VerificationMethod|Authentication|null
    {
        return VerificationMethod::where('verification_method_id', $didUrl->getFull())->first()
            ?? Authentication::where('authentication_id', $didUrl->getFull())->first();
    }
}

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();

    $this->app->bind('darauf.didResolvers.darauf', DaraufTestResolver::class);
});

function challengeUser(string $suffix, string $type = 'Ed25519VerificationKey2020'): array
{
    $did = 'did:darauf:'.$suffix;
    $keyPair = ed25519KeyPair();

    $document = DidDocument::factory()->create([
        'did_document_id' => $did,
    ]);

    VerificationMethod::factory()->create([
        'verification_method_id' => $did.'#key-1',
        'did_document_id' => $document->id,
        'serialized' => json_encode([
            'id' => $did.'#key-1',
            'type' => $type,
            'controller' => $did,
            'publicKeyMultibase' => $keyPair['publicKeyMultibase'],
        ]),
    ]);

    return [
        'did' => $did,
        'private' => $keyPair['private'],
    ];
}

it('generates a challenge for a registered resource', function () {
    $user = challengeUser('alice');

    $response = $this->postJson(route('darauf.challenge.generate'), [
        'didUrl' => $user['did'].'#key-1',
    ]);

    $response->assertCreated();

    $challenge = $response->json();

    expect($challenge)->toHaveKeys(['challengeId', 'nonce'])
        ->and(Cache::has('darauf_challenge:'.$challenge['challengeId']))->toBeTrue();
});

it('rejects a generate request for an unknown resource', function () {
    $this->postJson(route('darauf.challenge.generate'), [
        'didUrl' => 'did:darauf:ghost#key-1',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.resource_not_found'));
});

it('rejects a generate request for an unsupported verification method', function () {
    challengeUser('alice', 'RSA');

    $this->postJson(route('darauf.challenge.generate'), [
        'didUrl' => 'did:darauf:alice#key-1',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.verification_method_not_supported'));
});

it('rejects a malformed did url in the generate endpoint', function () {
    $this->postJson(route('darauf.challenge.generate'), [
        'didUrl' => 'not-a-did-url',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.invalid_did_url'));
});

it('rejects a missing didUrl in the generate endpoint', function () {
    $this->postJson(route('darauf.challenge.generate'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['didUrl']);
});

it('registers the named generate route without a method segment', function () {
    expect(route('darauf.challenge.generate'))
        ->toBe('http://localhost/api/darauf/v0.1.3/challenge/generate');
});

it('accepts a valid signature in the verify endpoint', function () {
    $user = challengeUser('alice');

    $challenge = $this->postJson(route('darauf.challenge.generate'), [
        'didUrl' => $user['did'].'#key-1',
    ])->assertCreated()->json();

    $signature = sodium_crypto_sign_detached($challenge['nonce'], $user['private']);

    $this->postJson(route('darauf.challenge.verify'), [
        'challengeId' => $challenge['challengeId'],
        'signature' => base64_encode($signature),
    ])->assertOk()
        ->assertJsonPath('message', __('darauf::messages.success.challenge_verified'));
});

it('rejects an invalid signature in the verify endpoint', function () {
    $user = challengeUser('alice');

    $challenge = $this->postJson(route('darauf.challenge.generate'), [
        'didUrl' => $user['did'].'#key-1',
    ])->assertCreated()->json();

    $signature = sodium_crypto_sign_detached('not-the-nonce', $user['private']);

    $this->postJson(route('darauf.challenge.verify'), [
        'challengeId' => $challenge['challengeId'],
        'signature' => base64_encode($signature),
    ])
        ->assertUnauthorized()
        ->assertJsonPath('message', __('darauf::messages.error.challenge_verification_failed'));
});

it('rejects a challenge that was never generated', function () {
    $this->postJson(route('darauf.challenge.verify'), [
        'challengeId' => 'missing-challenge-id',
        'signature' => base64_encode('signature'),
    ])
        ->assertUnauthorized()
        ->assertJsonPath('message', __('darauf::messages.error.challenge_not_found'));
});

it('is single use', function () {
    $user = challengeUser('alice');

    $challenge = $this->postJson(route('darauf.challenge.generate'), [
        'didUrl' => $user['did'].'#key-1',
    ])->assertCreated()->json();

    $signature = sodium_crypto_sign_detached($challenge['nonce'], $user['private']);
    $payload = [
        'challengeId' => $challenge['challengeId'],
        'signature' => base64_encode($signature),
    ];

    $this->postJson(route('darauf.challenge.verify'), $payload)->assertOk();

    $this->postJson(route('darauf.challenge.verify'), $payload)
        ->assertUnauthorized()
        ->assertJsonPath('message', __('darauf::messages.error.challenge_not_found'));
});

it('rejects a missing payload in the verify endpoint', function () {
    $this->postJson(route('darauf.challenge.verify'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['challengeId', 'signature']);
});

it('registers the named verify route without a method segment', function () {
    expect(route('darauf.challenge.verify'))
        ->toBe('http://localhost/api/darauf/v0.1.3/challenge/verify');
});
