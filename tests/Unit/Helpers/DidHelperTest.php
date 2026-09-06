<?php

declare(strict_types=1);

use Clicamal\Darauf\Exceptions\InvalidDidException;
use Clicamal\Darauf\Exceptions\InvalidDidWebIdException;
use Clicamal\Darauf\Exceptions\InvalidDidWebPathException;
use Clicamal\Darauf\Helpers\DidHelper;
use Illuminate\Support\Facades\Http;
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

it('preserves @context through validation', function () {
    $document = [
        '@context' => ['https://www.w3.org/ns/did/v1'],
        'id' => 'did:darauf:test',
    ];

    expect(DidHelper::validateDidDocument($document)['@context'])
        ->toBe(['https://www.w3.org/ns/did/v1']);
});

it('preserves @context as a string through validation', function () {
    $document = [
        '@context' => 'https://www.w3.org/ns/did/v1',
        'id' => 'did:darauf:test',
    ];

    expect(DidHelper::validateDidDocument($document)['@context'])
        ->toBe('https://www.w3.org/ns/did/v1');
});

it('isDidWeb detects did:web identifiers', function () {
    expect(DidHelper::isDidWeb('did:web:example.com'))->toBeTrue()
        ->and(DidHelper::isDidWeb('did:darauf:abc'))->toBeFalse();
});

it('converts did:web id to a well-known canonical URL', function () {
    expect(DidHelper::didWebIdToCanonicalUrl('did:web:example.com'))
        ->toBe('https://example.com/.well-known/did.json');
});

it('converts did:web id to a path canonical URL', function () {
    expect(DidHelper::didWebIdToCanonicalUrl('did:web:example.com:user:alice'))
        ->toBe('https://example.com/user/alice/did.json');
});

it('converts did:web id with percent-encoded port to a canonical URL', function () {
    expect(DidHelper::didWebIdToCanonicalUrl('did:web:example.com%3A3000:user'))
        ->toBe('https://example.com:3000/user/did.json');
});

it('converts did:web id to a root well-known darauf URL', function () {
    expect(DidHelper::didWebIdToDaraufUrl('did:web:example.com'))
        ->toBe('https://example.com/.well-known/did.json');
});

it('converts did:web id to a path darauf URL', function () {
    expect(DidHelper::didWebIdToDaraufUrl('did:web:example.com:user:alice'))
        ->toBe('https://example.com/diddocument/user/alice/did.json');
});

it('throws when did:web id is malformed', function () {
    DidHelper::didWebIdToCanonicalUrl('did:web');
})->throws(InvalidDidWebIdException::class);

it('round-trips a darauf path back to the same did:web id', function () {
    expect(DidHelper::didWebPathToId('example.com/diddocument/user/alice/did.json'))
        ->toBe('did:web:example.com:user:alice');
});

it('converts a well-known resolution path back to a did:web id', function () {
    expect(DidHelper::didWebPathToId('example.com/.well-known/did.json'))
        ->toBe('did:web:example.com');
});

it('converts a path resolution path back to a did:web id', function () {
    expect(DidHelper::didWebPathToId('example.com/user/alice/did.json'))
        ->toBe('did:web:example.com:user:alice');
});

it('throws when resolution path does not end in did.json', function () {
    DidHelper::didWebPathToId('example.com/user/alice/doc.json');
})->throws(InvalidDidWebPathException::class);

it('validates well-known resolution path', function () {
    expect(DidHelper::validateDidWebPath('example.com/.well-known/did.json'))->toBeTrue()
        ->and(DidHelper::validateDidWebPath('example.com/user/alice/did.json'))->toBeTrue()
        ->and(DidHelper::validateDidWebPath('example.com/wrong/did.txt'))->toBeFalse()
        ->and(DidHelper::validateDidWebPath('example.com/user/.well-known/did.json'))->toBeFalse();
});

it('validateDidWebDocument returns false for a malformed did:web id', function () {
    expect(DidHelper::validateDidWebDocument(['id' => 'did:web']))->toBeFalse();
});

it('validateDidWebDocument returns true when remote matches submission', function () {
    Http::fake([
        'https://example.com/.well-known/did.json' => Http::response([
            '@context' => ['https://www.w3.org/ns/did/v1'],
            'id' => 'did:web:example.com',
        ]),
    ]);

    $document = ['id' => 'did:web:example.com', '@context' => ['https://www.w3.org/ns/did/v1']];

    expect(DidHelper::validateDidWebDocument($document))->toBeTrue();
});

it('validateDidWebDocument returns false when remote differs from submission', function () {
    Http::fake([
        'https://example.com/.well-known/did.json' => Http::response([
            '@context' => ['https://www.w3.org/ns/did/v1'],
            'id' => 'did:web:example.com',
        ]),
    ]);

    $document = ['id' => 'did:web:example.com', '@context' => ['wrong-context']];

    expect(DidHelper::validateDidWebDocument($document))->toBeFalse();
});

it('validateDidWebDocument rejects private hosts to prevent ssrf', function () {
    Http::fake();

    expect(DidHelper::validateDidWebDocument(['id' => 'did:web:127.0.0.1']))
        ->toBeFalse()
        ->and(DidHelper::validateDidWebDocument(['id' => 'did:web:localhost:user']))
        ->toBeFalse();

    Http::assertNothingSent();
});
