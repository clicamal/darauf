<?php

declare(strict_types=1);

use Clicamal\Darauf\VerificationMethods\RSA\RSA;

function rsaPemKey(): string
{
    $key = openssl_pkey_new(['private_key_bits' => 2048]);

    return openssl_pkey_get_details($key)['key'];
}

function rsaMultibaseKey(): string
{
    $pem = rsaPemKey();

    $der = base64_decode(str_replace(["\n", "\r", '-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----'], '', $pem));

    return 'u'.base64urlEncode($der);
}

function base64urlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

it('validates a rsa multibase public key', function () {
    expect(RSA::validatePublicKey(rsaMultibaseKey()))->toBeTrue();
});

it('rejects a multibase key that is not base64url', function () {
    expect(RSA::validatePublicKey('u!!not-base64url!!'))->toBeFalse();
});

it('rejects a multibase key with an invalid payload', function () {
    expect(RSA::validatePublicKey('u'.base64urlEncode('short')))->toBeFalse();
});

it('rejects a plain string', function () {
    expect(RSA::validatePublicKey('not-a-key'))->toBeFalse();
});

it('rejects an empty string', function () {
    expect(RSA::validatePublicKey(''))->toBeFalse();
});

it('validates a rsa jwk', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $details = openssl_pkey_get_details($key);

    expect(RSA::validatePublicKey([
        'kty' => 'RSA',
        'n' => base64urlEncode($details['rsa']['n']),
        'e' => base64urlEncode($details['rsa']['e']),
    ]))->toBeTrue();
});

it('rejects a jwk without the required fields', function () {
    expect(RSA::validatePublicKey(['kty' => 'RSA']))
        ->toBeFalse();
});

it('rejects a jwk that is not rsa', function () {
    expect(RSA::validatePublicKey([
        'kty' => 'EC',
        'n' => 'abc',
        'e' => 'AQAB',
    ]))->toBeFalse();
});

it('rejects a jwk with an invalid modulus', function () {
    expect(RSA::validatePublicKey([
        'kty' => 'RSA',
        'n' => 'not-base64url',
        'e' => 'AQAB',
    ]))->toBeFalse();
});

it('rejects a jwk with a small modulus', function () {
    expect(RSA::validatePublicKey([
        'kty' => 'RSA',
        'n' => base64_encode(str_repeat('a', 8)),
        'e' => 'AQAB',
    ]))->toBeFalse();
});
