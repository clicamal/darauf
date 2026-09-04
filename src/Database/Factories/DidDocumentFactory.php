<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Database\Factories;

use Clicamal\Darauf\Helpers\DidHelper;
use Clicamal\Darauf\Models\DidDocument;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DidDocument>
 */
#[UseModel(DidDocument::class)]
class DidDocumentFactory extends Factory
{
    public function definition(): array
    {
        $didDocumentId = DidHelper::generateDid();

        return [
            'did_document_id' => $didDocumentId,
            'serialized' => json_encode([
                'id' => $didDocumentId,
            ]),
        ];
    }
}
