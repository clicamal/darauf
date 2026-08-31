<?php

declare(strict_types=1);

use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();
});

it('uses the correct table', function () {
    expect((new VerificationMethod)->getTable())->toBe('darauf_verification_methods');
});

it('uses a non-incrementing string primary key', function () {
    $method = new VerificationMethod;

    expect($method->getIncrementing())->toBeFalse()
        ->and($method->getKeyType())->toBe('string');
});

it('allows mass assignment of all fields', function () {
    DidDocument::create(['did' => 'did:darauf:test']);

    $method = VerificationMethod::create([
        'id' => 'did:darauf:test#key1',
        'did_document_did' => 'did:darauf:test',
        'controller' => 'did:darauf:test',
        'type' => 'RSA',
        'publicKeyMultibase' => 'test-key',
    ]);

    expect($method->id)->toBe('did:darauf:test#key1')
        ->and($method->did_document_did)->toBe('did:darauf:test')
        ->and($method->controller)->toBe('did:darauf:test')
        ->and($method->type)->toBe('RSA')
        ->and($method->publicKeyMultibase)->toBe('test-key');
});

it('belongs to a did document via its controller', function () {
    DidDocument::create(['did' => 'did:darauf:test']);

    $method = VerificationMethod::create([
        'id' => 'did:darauf:test#key1',
        'did_document_did' => 'did:darauf:test',
        'controller' => 'did:darauf:test',
        'type' => 'RSA',
        'publicKeyMultibase' => 'key',
    ]);

    expect($method->controller()->first())->not->toBeNull()
        ->and($method->controller()->first()->did)->toBe('did:darauf:test');
});
