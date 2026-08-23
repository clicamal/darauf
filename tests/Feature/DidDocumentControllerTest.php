<?php

declare(strict_types=1);

use Clicamal\Darauf\Models\DidDocument;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();
});

it('creates a did document with its verification method', function () {
    $publicKey = str_repeat('a', 451);

    $response = $this->postJson(route('darauf.diddocuments.create'), [
        'username' => 'alice',
        'publicKey' => $publicKey,
    ]);

    $did = 'did:darauf:'.hash('sha256', 'alice');

    $response->assertCreated()
        ->assertJsonPath('message', 'DID Document created.');

    $this->assertDatabaseHas('darauf_did_documents', [
        'did' => $did,
    ]);

    $this->assertDatabaseHas('darauf_verification_methods', [
        'id' => $did.'#key1',
        'controller' => $did,
        'type' => 'RSA',
        'public_key' => $publicKey,
    ]);
});

it('does not persist anything when the payload is empty', function () {
    $this->postJson(route('darauf.diddocuments.create'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['username', 'publicKey']);

    $this->assertDatabaseCount(DidDocument::class, 0)
        ->assertDatabaseCount('darauf_verification_methods', 0);
});

it('rejects usernames longer than 30 characters', function () {
    $this->postJson(route('darauf.diddocuments.create'), [
        'username' => str_repeat('a', 31),
        'publicKey' => 'key',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('accepts usernames with exactly 30 characters', function () {
    $this->postJson(route('darauf.diddocuments.create'), [
        'username' => str_repeat('a', 30),
        'publicKey' => 'key',
    ])->assertCreated();
});

it('rejects public keys longer than 451 characters', function () {
    $this->postJson(route('darauf.diddocuments.create'), [
        'username' => 'alice',
        'publicKey' => str_repeat('a', 452),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['publicKey']);
});

it('registers the named did document creation route', function () {
    expect(route('darauf.diddocuments.create'))
        ->toBe('http://localhost/api/darauf/v1/diddocuments');
});
