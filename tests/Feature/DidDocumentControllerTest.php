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
        ->assertJsonPath('did', $document['id']);

    $this->assertDatabaseHas('darauf_did_documents', [
        'did_document_id' => $document['id'],
    ]);

    $this->assertDatabaseHas('darauf_verification_methods', [
        'verification_method_id' => $document['verificationMethod'][0]['id'],
    ]);

    $persisted = DidDocument::where('did_document_id', $document['id'])->first();

    expect(json_decode($persisted->serialized, true)['id'])->toBe($document['id'])
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
        ->assertJsonValidationErrors(['verificationMethod.0.id']);

    $this->assertDatabaseCount(DidDocument::class, 0);
});

it('registers the named did document route', function () {
    expect(route('darauf.diddocuments.register'))
        ->toBe('http://localhost/api/darauf/v0.1.0/diddocuments');
});

it('stores the verification methods serialized', function () {
    $document = didDocumentData();

    $this->postJson(route('darauf.diddocuments.register'), $document)->assertCreated();

    $persisted = DidDocument::where('did_document_id', $document['id'])->first();
    $method = $persisted->verificationMethods()->first();

    expect(json_decode($method->serialized, true)['id'])->toBe($document['verificationMethod'][0]['id'])
        ->and(json_decode($method->serialized, true)['type'])->toBe('RSA')
        ->and(json_decode($method->serialized, true)['publicKeyMultibase'])->toStartWith('u');
});
