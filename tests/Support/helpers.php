<?php

declare(strict_types=1);

function base64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
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
