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

it('uses a non-incrementing string primary key', function () {
    $document = new DidDocument;

    expect($document->getIncrementing())->toBeFalse()
        ->and($document->getKeyType())->toBe('string');
});

it('allows mass assignment of did', function () {
    $document = DidDocument::create(['did' => 'did:darauf:test']);

    expect($document->did)->toBe('did:darauf:test');
});

it('can be found by did', function () {
    DidDocument::create(['did' => 'did:darauf:lookup']);

    expect(DidDocument::where('did', 'did:darauf:lookup')->first())
        ->not->toBeNull();
});

it('has many verification methods', function () {
    $document = DidDocument::create(['did' => 'did:darauf:test']);

    VerificationMethod::create([
        'id' => 'did:darauf:test#key1',
        'did_document_did' => 'did:darauf:test',
        'controller' => 'did:darauf:test',
        'type' => 'RSA',
        'publicKeyMultibase' => 'key',
    ]);

    expect($document->verificationMethods)->toHaveCount(1)
        ->and($document->verificationMethods->first()->id)->toBe('did:darauf:test#key1');
});

it('cascades delete to verification methods', function () {
    $document = DidDocument::create(['did' => 'did:darauf:test']);

    VerificationMethod::create([
        'id' => 'did:darauf:test#key1',
        'did_document_did' => 'did:darauf:test',
        'controller' => 'did:darauf:test',
        'type' => 'RSA',
        'publicKeyMultibase' => 'key',
    ]);

    $document->delete();

    $this->assertDatabaseCount(VerificationMethod::class, 0);
});

it('generates a did in the correct format', function () {
    $did = DidDocument::generateSha256DidFromUsername('alice');

    expect($did)->toStartWith('did:darauf:')
        ->and($did)->toBe('did:darauf:'.hash('sha256', 'alice'));
});

it('generates the same did for the same username', function () {
    expect(DidDocument::generateSha256DidFromUsername('alice'))
        ->toBe(DidDocument::generateSha256DidFromUsername('alice'));
});

it('generates different dids for different usernames', function () {
    expect(DidDocument::generateSha256DidFromUsername('alice'))
        ->not->toBe(DidDocument::generateSha256DidFromUsername('bob'));
});

it('validates a correct did', function () {
    expect(DidDocument::validateDid('did:darauf:abc123'))->toBe(1);
    expect(DidDocument::validateDid('did:web:example.com'))->toBe(1);
    expect(DidDocument::validateDid('did:key:z6Mk'))->toBe(1);
});

it('rejects an invalid did', function () {
    expect(DidDocument::validateDid('not-a-did'))->toBe(0);
    expect(DidDocument::validateDid('did:'))->toBe(0);
    expect(DidDocument::validateDid('DID:darauf:abc'))->toBe(0);
});
