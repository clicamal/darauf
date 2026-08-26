<?php

declare(strict_types=1);

use Clicamal\Darauf\Models\DidDocument;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();
});

function generatePublicKey(): string
{
    $key = openssl_pkey_new(['private_key_bits' => 2048]);

    return openssl_pkey_get_details($key)['key'];
}

it('creates a did document with its verification method', function () {
    $publicKey = generatePublicKey();

    $response = $this->postJson(route('darauf.diddocuments.create'), [
        'username' => 'alice',
        'publicKey' => $publicKey,
    ]);

    $did = 'did:darauf:'.hash('sha256', 'alice');

    $response->assertCreated()
        ->assertJsonPath('message', __('darauf::messages.success.create_did_document'));

    $this->assertDatabaseHas('darauf_did_documents', [
        'did' => $did,
    ]);

    $this->assertDatabaseHas('darauf_verification_methods', [
        'id' => $did.'#key1',
        'controller' => $did,
        'type' => 'RSA',
    ]);

    expect(DidDocument::first()?->verificationMethods()->first()?->public_key)
        ->toContain('BEGIN PUBLIC KEY');
});

it('rejects an empty payload', function () {
    $this->postJson(route('darauf.diddocuments.create'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['username', 'publicKey']);

    $this->assertDatabaseCount(DidDocument::class, 0);
});

it('rejects usernames longer than 30 characters', function () {
    $this->postJson(route('darauf.diddocuments.create'), [
        'username' => str_repeat('a', 31),
        'publicKey' => generatePublicKey(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('accepts usernames with exactly 30 characters', function () {
    $this->postJson(route('darauf.diddocuments.create'), [
        'username' => str_repeat('a', 30),
        'publicKey' => generatePublicKey(),
    ])
        ->assertCreated();
});

it('rejects public keys longer than 451 characters', function () {
    $this->postJson(route('darauf.diddocuments.create'), [
        'username' => 'alice',
        'publicKey' => str_repeat('a', 452),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['publicKey']);
});

it('rejects a public key that is not a valid PEM', function () {
    $this->postJson(route('darauf.diddocuments.create'), [
        'username' => 'alice',
        'publicKey' => 'not-a-valid-pem',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.invalid_public_key'));

    $this->assertDatabaseCount(DidDocument::class, 0);
});

it('rejects a duplicate username', function () {
    $publicKey = generatePublicKey();

    $payload = [
        'username' => 'alice',
        'publicKey' => $publicKey,
    ];

    $this->postJson(route('darauf.diddocuments.create'), $payload)
        ->assertCreated();

    $this->postJson(route('darauf.diddocuments.create'), $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.username_taken'));

    $this->assertDatabaseCount(DidDocument::class, 1);
});

it('registers the named did document creation route', function () {
    expect(route('darauf.diddocuments.create'))
        ->toBe('http://localhost/api/darauf/v1/diddocuments');
});
