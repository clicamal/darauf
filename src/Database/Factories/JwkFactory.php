<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Database\Factories;

use Clicamal\Darauf\Models\Jwk;
use Clicamal\Darauf\Models\VerificationMethod;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

#[UseModel(Jwk::class)]
class JwkFactory extends Factory
{
    public function definition(): array
    {
        $verificationMethod = VerificationMethod::factory()->make();

        return [
            'verification_method_id' => $verificationMethod->id,
            'kty' => fake()->asciify('******************************'),
            'use' => fake()->asciify('******************************'),
            'key_ops' => fake()->asciify('******************************'),
            'kid' => fake()->asciify('******************************'),
            'e' => fake()->asciify('******************************'),
            'n' => fake()->asciify('******************************'),
            'k' => fake()->asciify('******************************'),
        ];
    }
}
