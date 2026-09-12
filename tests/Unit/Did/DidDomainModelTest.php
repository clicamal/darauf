<?php

declare(strict_types=1);

use Clicamal\Darauf\Did\DidDomainModel;
use Clicamal\Darauf\Exceptions\ValidationException;

it('validates well-formed did strings', function () {
    expect(DidDomainModel::validate('did:darauf:abc123'))->toBeTrue()
        ->and(DidDomainModel::validate('did:web:example.com'))->toBeTrue()
        ->and(DidDomainModel::validate('did:key:z6Mk'))->toBeTrue();
});

it('rejects malformed did strings', function () {
    expect(DidDomainModel::validate('not-a-did'))->toBeFalse()
        ->and(DidDomainModel::validate('did:'))->toBeFalse()
        ->and(DidDomainModel::validate('DID:darauf:abc'))->toBeFalse();
});

it('parses the method name and method specific id', function () {
    $did = new DidDomainModel('did:web:example.com:user:alice');

    expect($did->getMethodName())->toBe('web')
        ->and($did->getMethodSpecificId())->toBe('example.com:user:alice')
        ->and($did->getFull())->toBe('did:web:example.com:user:alice');
});

it('rejects an invalid did on construction', function () {
    new DidDomainModel('not-a-did');
})->throws(ValidationException::class);
