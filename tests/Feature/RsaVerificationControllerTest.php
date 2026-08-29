<?php

declare(strict_types=1);

use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
use Clicamal\Darauf\Services\DidGenerator;
use Clicamal\Darauf\Services\RsaVerification\Challenge;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();
});

function createDidWithRsaMethod(string $username, string $publicKey): string
{
    $did = DidGenerator::generate($username);

    DidDocument::create(['did' => $did]);

    VerificationMethod::create([
        'id' => $did.'#key1',
        'controller' => $did,
        'type' => 'RSA',
        'public_key' => $publicKey,
    ]);

    return $did;
}

it('returns a challenge for an existing did with an rsa method', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $publicKey = openssl_pkey_get_details($key)['key'];

    createDidWithRsaMethod('alice', $publicKey);

    $response = $this->postJson(route('darauf.challenge.generate'), [
        'username' => 'alice',
    ]);

    $response->assertCreated();

    $challenge = $response->json('challenge');
    $challengeId = $challenge['challengeId'];

    expect($challenge)->toHaveKeys(['challengeId', 'challengeString'])
        ->and(Cache::has("darauf_rsa_challenge:{$challengeId}"))->toBeTrue();
});

it('verifies a signature against the generated challenge', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $publicKey = openssl_pkey_get_details($key)['key'];

    createDidWithRsaMethod('alice', $publicKey);

    $response = $this->postJson(route('darauf.challenge.generate'), [
        'username' => 'alice',
    ]);

    $challenge = $response->json('challenge');

    openssl_sign($challenge['challengeString'], $signature, $key);

    expect(Challenge::verify($challenge['challengeId'], $signature))->toBe(1);
});

it('rejects a username without a did document', function () {
    $this->postJson(route('darauf.challenge.generate'), [
        'username' => 'unknown',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.did_document_not_found'));
});

it('rejects a did without an rsa verification method', function () {
    DidDocument::create(['did' => 'did:darauf:'.hash('sha256', 'alice')]);

    $this->postJson(route('darauf.challenge.generate'), [
        'username' => 'alice',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.rsa_verification_method_not_found'));
});

it('rejects a missing username', function () {
    $this->postJson(route('darauf.challenge.generate'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('rejects usernames longer than 30 characters', function () {
    $this->postJson(route('darauf.challenge.generate'), [
        'username' => str_repeat('a', 31),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('registers the named challenge route', function () {
    expect(route('darauf.challenge.generate'))
        ->toBe('http://localhost/api/darauf/v1/challenge');
});
