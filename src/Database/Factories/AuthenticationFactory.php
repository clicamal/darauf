<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Database\Factories;

use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\DidDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Authentication>
 */
class AuthenticationFactory extends Factory
{
    public function definition(): array
    {
        $didDocument = DidDocument::factory()->create();
        $publicKey = sodium_crypto_sign_publickey(sodium_crypto_sign_keypair());

        return [
            'authentication_id' => $didDocument->did_document_id.'#key-1',
            'did_document_id' => $didDocument->id,
            'serialized' => json_encode([
                'id' => $didDocument->did_document_id.'#key-1',
                'type' => 'JsonWebKey2020',
                'controller' => $didDocument->did_document_id,
                'publicKeyJwk' => [
                    'kty' => 'OKP',
                    'crv' => 'Ed25519',
                    'x' => rtrim(strtr(base64_encode($publicKey), '+/', '-_'), '='),
                ],
            ]),
        ];
    }

    public function modelName(): string
    {
        return Authentication::class;
    }
}
