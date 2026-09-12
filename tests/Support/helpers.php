<?php

declare(strict_types=1);

function base64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base58_encode(string $value): string
{
    if ($value === '') {
        return '';
    }

    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $bytes = array_values(unpack('C*', $value));

    $leadingZeros = 0;

    foreach ($bytes as $byte) {
        if ($byte !== 0) {
            break;
        }

        $leadingZeros++;
    }

    $digits = [0];

    foreach ($bytes as $byte) {
        $carry = $byte;

        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $carry += $digits[$i] << 8;
            $digits[$i] = $carry % 58;
            $carry = intdiv($carry, 58);
        }

        while ($carry > 0) {
            array_unshift($digits, $carry % 58);
            $carry = intdiv($carry, 58);
        }
    }

    while (count($digits) > 1 && $digits[0] === 0) {
        array_shift($digits);
    }

    $isZero = count($digits) === 1 && $digits[0] === 0;

    $result = str_repeat('1', $isZero ? max(0, $leadingZeros - 1) : $leadingZeros);

    foreach ($digits as $digit) {
        $result .= $alphabet[$digit];
    }

    return $result;
}

function rsaKeyPair(): array
{
    $private = openssl_pkey_new(['private_key_bits' => 2048]);

    $der = base64_decode(str_replace(["\n", "\r", '-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----'], '', openssl_pkey_get_details($private)['key']));

    return [
        'private' => $private,
        'publicKeyMultibase' => 'u'.base64url_encode($der),
    ];
}

function ed25519KeyPair(): array
{
    $keypair = sodium_crypto_sign_keypair();

    return [
        'private' => sodium_crypto_sign_secretkey($keypair),
        'public' => sodium_crypto_sign_publickey($keypair),
        'publicKeyMultibase' => 'z'.base58_encode("\xed\x01".sodium_crypto_sign_publickey($keypair)),
        'publicKeyJwk' => [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => base64url_encode(sodium_crypto_sign_publickey($keypair)),
        ],
    ];
}

function didDocumentData(string $did = 'did:darauf:test', array $overrides = []): array
{
    return array_replace_recursive([
        'id' => $did,
        'verificationMethod' => [
            [
                'id' => $did.'#key-1',
                'type' => 'RSA',
                'controller' => $did,
                'publicKeyMultibase' => rsaKeyPair()['publicKeyMultibase'],
            ],
        ],
    ], $overrides);
}
