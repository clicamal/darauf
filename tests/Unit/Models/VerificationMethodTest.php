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

it('uses an incrementing integer primary key', function () {
    $method = new VerificationMethod;

    expect($method->getIncrementing())->toBeTrue()
        ->and($method->getKeyType())->toBe('int');
});

it('allows mass assignment of its fillable fields', function () {
    $document = DidDocument::factory()->create();

    $method = VerificationMethod::create([
        'verification_method_id' => $document->did_document_id.'#key-1',
        'did_document_id' => $document->id,
        'serialized' => json_encode(['id' => $document->did_document_id.'#key-1']),
    ]);

    expect($method->verification_method_id)->toBe($document->did_document_id.'#key-1')
        ->and($method->did_document_id)->toBe($document->id)
        ->and($method->payload['id'])->toBe($document->did_document_id.'#key-1');
});

it('has a unique verification method id', function () {
    $document = DidDocument::factory()->create();

    VerificationMethod::factory()->create([
        'did_document_id' => $document->id,
    ]);

    $existing = VerificationMethod::first();

    expect(fn () => VerificationMethod::factory()->create([
        'verification_method_id' => $existing->verification_method_id,
        'did_document_id' => $document->id,
    ]))->toThrow(RuntimeException::class);
});

it('belongs to a did document', function () {
    $method = VerificationMethod::factory()->create();

    expect($method->didDocument)->not->toBeNull()
        ->and($method->didDocument)->toBeInstanceOf(DidDocument::class)
        ->and($method->didDocument->id)->toBe($method->did_document_id);
});

it('creates a coherent method through its factory', function () {
    $method = VerificationMethod::factory()->create();

    expect($method->verification_method_id)->toContain('#key-1')
        ->and($method->payload['id'])->toBe($method->verification_method_id)
        ->and($method->type)->toBe('RSA')
        ->and($method->publicKeyMultibase)->toStartWith('u');
});

it('exposes the serialized payload through the concern', function () {
    $method = new VerificationMethod([
        'serialized' => json_encode([
            'id' => 'did:darauf:test#key-1',
            'type' => 'Ed25519VerificationKey2020',
            'publicKeyMultibase' => 'z6Mk',
            'publicKeyJwk' => ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'abc'],
        ]),
    ]);

    expect($method->payload)->toBe([
        'id' => 'did:darauf:test#key-1',
        'type' => 'Ed25519VerificationKey2020',
        'publicKeyMultibase' => 'z6Mk',
        'publicKeyJwk' => ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'abc'],
    ])->and($method->type)->toBe('Ed25519VerificationKey2020')
        ->and($method->publicKeyMultibase)->toBe('z6Mk')
        ->and($method->publicKeyJwk)->toBe(['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'abc']);
});

it('returns null for missing serialized member fields', function () {
    $method = new VerificationMethod([
        'serialized' => json_encode(['id' => 'did:darauf:test#key-1']),
    ]);

    expect($method->type)->toBeNull()
        ->and($method->publicKeyMultibase)->toBeNull()
        ->and($method->publicKeyJwk)->toBeNull();
});
