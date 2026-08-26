<?php

declare(strict_types=1);

use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;

beforeEach(function () {
    $this->app['config']->set('database.connections.testing.foreign_key_constraints', true);

    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();
});

it('uses the correct table', function () {
    expect((new DidDocument)->getTable())->toBe('darauf_did_documents');
});

it('allows mass assignment of did', function () {
    $document = DidDocument::create(['did' => 'did:darauf:test']);

    expect($document->did)->toBe('did:darauf:test');
});

it('has many verification methods', function () {
    $document = DidDocument::create(['did' => 'did:darauf:test']);

    VerificationMethod::create([
        'id' => 'did:darauf:test#key1',
        'controller' => 'did:darauf:test',
        'type' => 'RSA',
        'public_key' => 'key',
    ]);

    expect($document->verificationMethods)->toHaveCount(1)
        ->and($document->verificationMethods->first()->controller)->toBe('did:darauf:test');
});

it('cascades delete to verification methods', function () {
    $document = DidDocument::create(['did' => 'did:darauf:test']);

    VerificationMethod::create([
        'id' => 'did:darauf:test#key1',
        'controller' => 'did:darauf:test',
        'type' => 'RSA',
        'public_key' => 'key',
    ]);

    $document->delete();

    $this->assertDatabaseCount(VerificationMethod::class, 0);
});

it('can be found by did', function () {
    DidDocument::create(['did' => 'did:darauf:lookup']);

    expect(DidDocument::where('did', 'did:darauf:lookup')->first())
        ->not->toBeNull();
});
