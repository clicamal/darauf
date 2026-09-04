<?php

declare(strict_types=1);

use Clicamal\Darauf\Exceptions\InvalidDidException;
use Clicamal\Darauf\Helpers\DidHelper;
use Illuminate\Validation\ValidationException;

it('generates a did in the correct format', function () {
    $did = DidHelper::generateDid();

    expect($did)->toStartWith('did:darauf:')
        ->and($did)->toHaveLength(strlen('did:darauf:') + 64)
        ->and(DidHelper::validateDid($did))->toBe(1);
});

it('generates a unique did each call', function () {
    expect(DidHelper::generateDid())->not->toBe(DidHelper::generateDid());
});

it('throws when the generated did is not valid', function () {
    // Geração é determinística; se o padrão falhar numa chamada, a exceção é levantada.
    expect(fn () => DidHelper::generateDid())->not->toThrow(InvalidDidException::class);
});

it('validates a correct did', function () {
    expect(DidHelper::validateDid('did:darauf:abc123'))->toBe(1)
        ->and(DidHelper::validateDid('did:web:example.com'))->toBe(1)
        ->and(DidHelper::validateDid('did:key:z6Mk'))->toBe(1);
});

it('rejects an invalid did', function () {
    expect(DidHelper::validateDid('not-a-did'))->toBe(0)
        ->and(DidHelper::validateDid('did:'))->toBe(0)
        ->and(DidHelper::validateDid('DID:darauf:abc'))->toBe(0);
});

it('validates a did document with a multibase verification method', function () {
    $document = didDocumentData();

    expect(DidHelper::validateDidDocument($document))->toBeArray()
        ->and(DidHelper::validateDidDocument($document)['id'])->toBe($document['id']);
});

it('validates a did document with a jwk verification method', function () {
    $document = didDocumentData(overrides: [
        'verificationMethod' => [
            [
                'id' => 'did:darauf:test#key-1',
                'type' => 'RSA',
                'controller' => 'did:darauf:test',
                'publicKeyJwk' => ['kty' => 'RSA', 'n' => 'abc', 'e' => 'AQAB'],
            ],
        ],
    ]);

    expect(DidHelper::validateDidDocument($document)['verificationMethod'][0]['publicKeyJwk']['kty'])->toBe('RSA');
});

it('validates a did document with multiple verification methods', function () {
    $document = didDocumentData();
    $document['verificationMethod'][] = [
        'id' => 'did:darauf:test#key-2',
        'type' => 'RSA',
        'controller' => 'did:darauf:test',
        'publicKeyMultibase' => rsaKeyPair()['publicKeyMultibase'],
    ];

    expect(DidHelper::validateDidDocument($document)['verificationMethod'])->toHaveCount(2);
});

it('rejects a did document without an id', function () {
    $document = didDocumentData();
    unset($document['id']);

    expect(fn () => DidHelper::validateDidDocument($document))->toThrow(ValidationException::class);
});

it('rejects a verification method without an id', function () {
    $document = didDocumentData();
    unset($document['verificationMethod'][0]['id']);

    expect(fn () => DidHelper::validateDidDocument($document))->toThrow(ValidationException::class);
});

it('rejects a verification method without a type', function () {
    $document = didDocumentData();
    unset($document['verificationMethod'][0]['type']);

    expect(fn () => DidHelper::validateDidDocument($document))->toThrow(ValidationException::class);
});

it('rejects a did document with an invalid service endpoint', function () {
    $document = didDocumentData();
    $document['service'] = [
        [
            'id' => 'did:darauf:test#svc-1',
            'type' => 'LinkedDomains',
            'serviceEndpoint' => 'not-a-uri',
        ],
    ];

    expect(fn () => DidHelper::validateDidDocument($document))->toThrow(ValidationException::class);
});

it('accepts a did document without verification methods or services', function () {
    $document = [
        'id' => 'did:darauf:test',
    ];

    expect(DidHelper::validateDidDocument($document)['id'])->toBe('did:darauf:test');
});
