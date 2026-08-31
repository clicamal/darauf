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

function rsaUser(string $username): array
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

it('returns a challenge for a registered user', function () {
    $user = rsaUser('alice');

    $response = $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'username' => 'alice',
    ]);

    $response->assertCreated();

    $challenge = $response->json();

    expect($challenge)->toHaveKeys(['id', 'string'])
        ->and(Cache::has("darauf_rsa_challenge:{$challenge['id']}"))->toBeTrue();
});

it('rejects a challenge for an unregistered user', function () {
    $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'username' => 'ghost',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.did_document_not_found'));
});

it('rejects a username that is too long', function () {
    $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'username' => str_repeat('a', 31),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('registers the named challenge generate route', function () {
    expect(route('darauf.verification.challenge.generate', ['method' => 'RSA']))
        ->toBe('http://localhost/api/darauf/v0.1.0/challenge/generate/RSA');
});

it('accepts a valid signature in the verify endpoint', function () {
    $user = rsaUser('alice');

    $challengeResponse = $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'username' => 'alice',
    ])->assertCreated();

    $challenge = $challengeResponse->json();

    openssl_sign($challenge['string'], $signature, $user['private']);

    $this->postJson(route('darauf.verification.challenge.verify', ['method' => 'RSA']), [
        'challengeId' => $challenge['id'],
        'signature' => base64_encode($signature),
    ])->assertOk();
});

it('rejects an invalid signature in the verify endpoint', function () {
    $user = rsaUser('alice');

    $challengeResponse = $this->postJson(route('darauf.verification.challenge.generate', ['method' => 'RSA']), [
        'username' => 'alice',
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
        'signature' => 'signature',
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
