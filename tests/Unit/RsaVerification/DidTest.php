<?php

declare(strict_types=1);

use Clicamal\Darauf\Services\Did;

it('generates a did in the correct format', function () {
    $did = Did::generate('alice');

    expect($did)->toStartWith('did:darauf:')
        ->and($did)->toBe('did:darauf:'.hash('sha256', 'alice'));
});

it('generates the same did for the same username', function () {
    expect(Did::generate('alice'))->toBe(Did::generate('alice'));
});

it('generates different dids for different usernames', function () {
    expect(Did::generate('alice'))->not->toBe(Did::generate('bob'));
});

it('validates a correct did', function () {
    expect(Did::validate('did:darauf:abc123'))->toBe(1);
    expect(Did::validate('did:web:example.com'))->toBe(1);
    expect(Did::validate('did:key:z6Mk'))->toBe(1);
});

it('rejects an invalid did', function () {
    expect(Did::validate('not-a-did'))->toBe(0);
    expect(Did::validate('did:'))->toBe(0);
    expect(Did::validate('DID:darauf:abc'))->toBe(0);
});
