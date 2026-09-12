<?php

declare(strict_types=1);

use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();

    Schema::enableForeignKeyConstraints();
});

it('uses the correct table', function () {
    expect((new DidDocument)->getTable())->toBe('darauf_did_documents');
});

it('uses an incrementing integer primary key', function () {
    $document = new DidDocument;

    expect($document->getIncrementing())->toBeTrue()
        ->and($document->getKeyType())->toBe('int');
});

it('allows mass assignment of its fillable fields', function () {
    $document = DidDocument::create([
        'did_document_id' => 'did:darauf:test',
        'serialized' => json_encode(['id' => 'did:darauf:test']),
    ]);

    expect($document->did_document_id)->toBe('did:darauf:test')
        ->and($document->serialized)->toBe('{"id":"did:darauf:test"}');
});

it('has a unique did document id', function () {
    DidDocument::create([
        'did_document_id' => 'did:darauf:test',
        'serialized' => json_encode(['id' => 'did:darauf:test']),
    ]);

    expect(fn () => DidDocument::create([
        'did_document_id' => 'did:darauf:test',
        'serialized' => json_encode(['id' => 'did:darauf:test']),
    ]))->toThrow(RuntimeException::class);
});

it('has many verification methods', function () {
    $document = DidDocument::factory()->create();

    $method = VerificationMethod::factory()->create([
        'did_document_id' => $document->id,
    ]);

    expect($document->verificationMethods)->toHaveCount(1)
        ->and($document->verificationMethods->first()->id)->toBe($method->id);
});

it('has many authentications', function () {
    $document = DidDocument::factory()->create();

    $authentication = Authentication::factory()->create([
        'did_document_id' => $document->id,
    ]);

    expect($document->authentications)->toHaveCount(1)
        ->and($document->authentications->first()->id)->toBe($authentication->id);
});

it('persists verification method and authentication members from the payload', function () {
    $did = 'did:darauf:test';

    DidDocument::create([
        'did_document_id' => $did,
        'serialized' => json_encode([
            'id' => $did,
            'verificationMethod' => [
                ['id' => $did.'#key-1', 'controller' => $did, 'type' => 'RSA', 'publicKeyMultibase' => 'uabc'],
            ],
            'authentication' => [
                ['id' => $did.'#key-1', 'controller' => $did, 'type' => 'JsonWebKey2020', 'publicKeyJwk' => ['kty' => 'OKP']],
            ],
        ]),
    ]);

    $document = DidDocument::where('did_document_id', $did)->first();

    expect($document->verificationMethods)->toHaveCount(1)
        ->and($document->verificationMethods->first()->verification_method_id)->toBe($did.'#key-1')
        ->and($document->authentications)->toHaveCount(1)
        ->and($document->authentications->first()->authentication_id)->toBe($did.'#key-1');
});

it('cascades delete to verification methods', function () {
    $document = DidDocument::factory()->create();

    VerificationMethod::factory()->create([
        'did_document_id' => $document->id,
    ]);

    $document->delete();

    $this->assertDatabaseCount(VerificationMethod::class, 0);
});

it('cascades delete to authentications', function () {
    $document = DidDocument::factory()->create();

    Authentication::factory()->create([
        'did_document_id' => $document->id,
    ]);

    $document->delete();

    $this->assertDatabaseCount(Authentication::class, 0);
});

it('creates a valid document through its factory', function () {
    $document = DidDocument::factory()->create();

    expect($document->did_document_id)->toStartWith('did:darauf:')
        ->and($document->payload['id'])->toBe($document->did_document_id);
});
