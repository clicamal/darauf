<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Database\Factories;

use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VerificationMethod>
 */
#[UseModel(VerificationMethod::class)]
class VerificationMethodFactory extends Factory
{
    public function definition(): array
    {
        $didDocument = DidDocument::factory()->create();

        return [
            'verification_method_id' => $didDocument->did_document_id.'#key-1',
            'did_document_id' => $didDocument->id,
            'serialized' => json_encode([
                'id' => $didDocument->did_document_id.'#key-1',
                'type' => 'RSA',
                'controller' => $didDocument->did_document_id,
                'publicKeyMultibase' => 'u'.Str::random(43),
            ]),
        ];
    }
}
