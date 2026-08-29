<?php

declare(strict_types=1);

use Clicamal\Darauf\Services\DidGenerator;

it('generates a did in the correct format', function () {
    $did = DidGenerator::generate('alice');

    expect($did)->toStartWith('did:darauf:')
        ->and($did)->toBe('did:darauf:'.hash('sha256', 'alice'));
});

it('generates the same did for the same username', function () {
    expect(DidGenerator::generate('alice'))->toBe(DidGenerator::generate('alice'));
});

it('generates different dids for different usernames', function () {
    expect(DidGenerator::generate('alice'))->not->toBe(DidGenerator::generate('bob'));
});

it('validates a correct did', function () {
    expect(DidGenerator::validate('did:darauf:abc123'))->toBe(1);
    expect(DidGenerator::validate('did:web:example.com'))->toBe(1);
    expect(DidGenerator::validate('did:key:z6Mk'))->toBe(1);
});

it('rejects an invalid did', function () {
    expect(DidGenerator::validate('not-a-did'))->toBe(0);
    expect(DidGenerator::validate('did:'))->toBe(0);
    expect(DidGenerator::validate('DID:darauf:abc'))->toBe(0);
});
