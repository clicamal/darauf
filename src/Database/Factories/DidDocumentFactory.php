<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Database\Factories;

use Clicamal\Darauf\Models\DidDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DidDocument>
 */
class DidDocumentFactory extends Factory
{
    public function definition(): array
    {
        $didDocumentId = 'did:darauf:'.hash('sha256', Str::random(32));

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
