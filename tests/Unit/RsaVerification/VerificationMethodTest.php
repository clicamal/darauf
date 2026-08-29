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
        'controller' => 'did:darauf:test',
        'type' => 'RSA',
        'public_key' => 'test-key',
    ]);

    expect($method->id)->toBe('did:darauf:test#key1')
        ->and($method->controller)->toBe('did:darauf:test')
        ->and($method->type)->toBe('RSA')
        ->and($method->public_key)->toBe('test-key');
});

it('belongs to a did document', function () {
    DidDocument::create(['did' => 'did:darauf:test']);

    $method = VerificationMethod::create([
        'id' => 'did:darauf:test#key1',
        'controller' => 'did:darauf:test',
        'type' => 'RSA',
        'public_key' => 'key',
    ]);

    expect($method->didDocument)->not->toBeNull()
        ->and($method->didDocument->did)->toBe('did:darauf:test');
});
