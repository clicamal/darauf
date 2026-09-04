<?php

declare(strict_types=1);

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

it('cascades delete to verification methods', function () {
    $document = DidDocument::factory()->create();

    VerificationMethod::factory()->create([
        'did_document_id' => $document->id,
    ]);

    $document->delete();

    $this->assertDatabaseCount(VerificationMethod::class, 0);
});

it('creates a valid document through its factory', function () {
    $document = DidDocument::factory()->create();

    expect($document->did_document_id)->toStartWith('did:darauf:')
        ->and(json_decode($document->serialized, true)['id'])->toBe($document->did_document_id);
});
