<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Database\Factories;

use Clicamal\Darauf\Models\DidDocument;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

#[UseModel(DidDocument::class)]
class DidDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'did' => 'did:darauf:'.fake()->asciify('******************************'),
        ];
    }
}
