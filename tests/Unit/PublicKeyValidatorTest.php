<?php

declare(strict_types=1);

use Clicamal\Darauf\Services\PublicKeyValidator;

it('validates a real rsa public key', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $pem = openssl_pkey_get_details($key)['key'];

    expect(PublicKeyValidator::validate($pem))->toBeObject();
});

it('rejects a plain string', function () {
    expect(PublicKeyValidator::validate('not-a-key'))->toBeFalse();
});

it('rejects an empty string', function () {
    expect(PublicKeyValidator::validate(''))->toBeFalse();
});
