<?php

declare(strict_types=1);

use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\Jwk;
use Clicamal\Darauf\Models\VerificationMethod;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();
});

it('uses the correct table', function () {
    expect((new Jwk)->getTable())->toBe('darauf_jwks');
});

it('allows mass assignment of all fields', function () {
    DidDocument::create(['did' => 'did:darauf:test']);

    VerificationMethod::create([
        'id' => 'did:darauf:test#key1',
        'did_document_did' => 'did:darauf:test',
        'controller' => 'did:darauf:test',
        'type' => 'RSA',
        'publicKeyMultibase' => 'key',
    ]);

    $jwk = Jwk::create([
        'verification_method_id' => 'did:darauf:test#key1',
        'kty' => 'RSA',
        'use' => 'sig',
        'key_ops' => 'verify',
        'kid' => 'did:darauf:test#key1',
        'e' => 'AQAB',
        'n' => 'base64url-n',
        'k' => 'base64url-k',
    ]);

    expect($jwk->kty)->toBe('RSA')
        ->and($jwk->use)->toBe('sig')
        ->and($jwk->key_ops)->toBe('verify')
        ->and($jwk->kid)->toBe('did:darauf:test#key1')
        ->and($jwk->e)->toBe('AQAB')
        ->and($jwk->n)->toBe('base64url-n')
        ->and($jwk->k)->toBe('base64url-k');
});

it('belongs to a verification method', function () {
    DidDocument::create(['did' => 'did:darauf:test']);

    VerificationMethod::create([
        'id' => 'did:darauf:test#key1',
        'did_document_did' => 'did:darauf:test',
        'controller' => 'did:darauf:test',
        'type' => 'RSA',
        'publicKeyMultibase' => 'key',
    ]);

    Jwk::create([
        'verification_method_id' => 'did:darauf:test#key1',
        'kty' => 'RSA',
        'use' => 'sig',
        'key_ops' => 'verify',
        'kid' => 'did:darauf:test#key1',
        'e' => 'AQAB',
        'n' => 'base64url-n',
        'k' => 'base64url-k',
    ]);

    $verificationMethod = VerificationMethod::find('did:darauf:test#key1');
    $jwk = Jwk::where('verification_method_id', 'did:darauf:test#key1')->first();

    expect($jwk->verificationMethod)->not->toBeNull()
        ->and($jwk->verificationMethod->id)->toBe($verificationMethod->id);
});
