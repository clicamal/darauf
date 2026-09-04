<?php

declare(strict_types=1);

namespace Clicamal\Darauf\VerificationMethods;

interface ChallengeVerifierContract
{
    /**
     * Validates the request data for generating a challenge.
     *
     * @param  array<string, mixed>  $requestAll
     * @return array<string, mixed>
     */
    public static function validateGenerateChallengeRequest(array $requestAll): array;

    /**
     * Validates the request data for verifying a challenge.
     *
     * @param  array<string, mixed>  $requestAll
     * @return array<string, mixed>
     */
    public static function validateVerifyChallengeRequest(array $requestAll): array;

    /**
     * Generates a challenge for the verification method.
     *
     * @param  array<string, mixed>  $data
     * @return array{id: string, string: string}
     */
    public static function generateChallenge(array $data): array;

    /**
     * Verifies a challenge for the verification method.
     *
     * @param  array<string, mixed>  $data
     */
    public static function verifyChallenge(array $data): bool;
}
