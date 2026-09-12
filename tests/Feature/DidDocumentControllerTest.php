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
    $document = didDocumentData();

    $response = $this->postJson(route('darauf.diddocuments.register'), $document);

    $response->assertCreated()
        ->assertJsonPath('message', __('darauf::messages.success.did_document_registered'));

    $this->assertDatabaseHas('darauf_did_documents', [
        'did_document_id' => $document['id'],
    ]);

    $this->assertDatabaseHas('darauf_verification_methods', [
        'verification_method_id' => $document['verificationMethod'][0]['id'],
    ]);

    $persisted = DidDocument::where('did_document_id', $document['id'])->first();

    expect($persisted->payload['id'])->toBe($document['id'])
        ->and($persisted->verificationMethods)->toHaveCount(1);
});

it('rejects an empty payload', function () {
    $this->postJson(route('darauf.diddocuments.register'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id']);

    $this->assertDatabaseCount(DidDocument::class, 0);
});

it('rejects a did document without an id', function () {
    $document = didDocumentData();
    unset($document['id']);

    $this->postJson(route('darauf.diddocuments.register'), $document)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id']);

    $this->assertDatabaseCount(DidDocument::class, 0);
});

it('rejects a verification method without an id', function () {
    $document = didDocumentData();
    unset($document['verificationMethod'][0]['id']);

    $this->postJson(route('darauf.diddocuments.register'), $document)
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.invalid_did_document'));

    $this->assertDatabaseCount(DidDocument::class, 0);
});

it('registers the named did document route', function () {
    expect(route('darauf.diddocuments.register'))
        ->toBe('http://localhost/api/darauf/v0.1.3/diddocuments');
});

it('stores the verification methods serialized', function () {
    $document = didDocumentData();

    $this->postJson(route('darauf.diddocuments.register'), $document)->assertCreated();

    $persisted = DidDocument::where('did_document_id', $document['id'])->first();
    $method = $persisted->verificationMethods()->first();

    expect($method->payload['id'])->toBe($document['verificationMethod'][0]['id'])
        ->and($method->type)->toBe('RSA')
        ->and($method->publicKeyMultibase)->toStartWith('u');
});

it('stores the authentication members serialized', function () {
    $document = didDocumentData(overrides: [
        'authentication' => [
            [
                'id' => 'did:darauf:test#auth-1',
                'controller' => 'did:darauf:test',
                'type' => 'JsonWebKey2020',
                'publicKeyJwk' => ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'abc'],
            ],
        ],
    ]);

    $this->postJson(route('darauf.diddocuments.register'), $document)->assertCreated();

    $persisted = DidDocument::where('did_document_id', $document['id'])->first();

    $this->assertDatabaseHas('darauf_authentication', [
        'authentication_id' => $document['authentication'][0]['id'],
    ]);

    $authentication = $persisted->authentications()->first();

    expect($authentication->payload['id'])->toBe($document['authentication'][0]['id'])
        ->and($authentication->type)->toBe('JsonWebKey2020')
        ->and($authentication->publicKeyJwk)->toBe(['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'abc']);
});

it('rejects a duplicate did document id', function () {
    $document = didDocumentData();

    $this->postJson(route('darauf.diddocuments.register'), $document)->assertCreated();

    $this->postJson(route('darauf.diddocuments.register'), $document)
        ->assertUnprocessable()
        ->assertJsonPath('message', __('darauf::messages.error.general'));
});
