<?php

declare(strict_types=1);

namespace Clicamal\Darauf\ChallengeManagers;

use Illuminate\Contracts\Validation\Validator;

interface ChallengeManagerContract
{
    /**
     * Validates the request data for generating a challenge.
     *
     * @param  array<string, mixed>  $requestAll
     */
    public function getGenerateChallengeRequestValidator(array $requestAll): Validator;

    /**
     * Validates the request data for verifying a challenge.
     *
     * @param  array<string, mixed>  $requestAll
     */
    public function getValidateChallengeRequestValidator(array $requestAll): Validator;

    /**
     * Generates a challenge for the verification method.
     *
     * @param  array<string, mixed>  $data
     * @return array{id: string, string: string}
     */
    public function generateChallenge(array $data): array;

    /**
     * Verifies a challenge for the verification method.
     *
     * @param  array<string, mixed>  $data
     */
    public function verifyChallenge(array $data): bool;
}
