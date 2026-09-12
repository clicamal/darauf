<?php

declare(strict_types=1);

use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\DidDocument;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../../../database/migrations'),
        '--realpath' => true,
    ])->assertSuccessful();
});

it('uses the correct table', function () {
    expect((new Authentication)->getTable())->toBe('darauf_authentication');
});

it('uses an incrementing integer primary key', function () {
    $authentication = new Authentication;

    expect($authentication->getIncrementing())->toBeTrue()
        ->and($authentication->getKeyType())->toBe('int');
});

it('allows mass assignment of its fillable fields', function () {
    $document = DidDocument::factory()->create();

    $authentication = Authentication::create([
        'authentication_id' => $document->did_document_id.'#key-1',
        'did_document_id' => $document->id,
        'serialized' => json_encode(['id' => $document->did_document_id.'#key-1']),
    ]);

    expect($authentication->authentication_id)->toBe($document->did_document_id.'#key-1')
        ->and($authentication->did_document_id)->toBe($document->id)
        ->and($authentication->payload['id'])->toBe($document->did_document_id.'#key-1');
});

it('belongs to a did document', function () {
    $authentication = Authentication::factory()->create();

    expect($authentication->didDocument)->not->toBeNull()
        ->and($authentication->didDocument)->toBeInstanceOf(DidDocument::class)
        ->and($authentication->didDocument->id)->toBe($authentication->did_document_id);
});

it('creates a coherent authentication through its factory', function () {
    $authentication = Authentication::factory()->create();

    expect($authentication->authentication_id)->toContain('#key-1')
        ->and($authentication->payload['id'])->toBe($authentication->authentication_id)
        ->and($authentication->type)->toBe('JsonWebKey2020')
        ->and($authentication->publicKeyJwk)->toHaveKeys(['kty', 'crv', 'x']);
});
