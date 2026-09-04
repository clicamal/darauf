<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Database\Factories;

use Clicamal\Darauf\Helpers\DidHelper;
use Clicamal\Darauf\Models\DidDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DidDocument>
 */
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

    public function modelName(): string
    {
        return DidDocument::class;
    }
}
