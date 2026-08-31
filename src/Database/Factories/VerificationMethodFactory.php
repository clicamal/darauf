<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Database\Factories;

use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

#[UseModel(VerificationMethod::class)]
class VerificationMethodFactory extends Factory
{
    public function definition(): array
    {
        $didDocument = DidDocument::factory()->make();

        return [
            'id' => 'did:darauf:'.fake()->asciify('******************************').'#key1',
            'did_document_did' => $didDocument->did,
            'controller' => $didDocument->did,
            'type' => fake()->asciify('***************'),
            'publicKeyMultibase' => fake()->asciify('******************************'),
        ];
    }
}
