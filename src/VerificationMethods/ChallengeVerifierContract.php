<?php

declare(strict_types=1);

namespace Clicamal\Darauf\VerificationMethods;

interface ChallengeVerifierContract
{
    /**
     * Validates the request data for generating a challenge.
     */
    public static function validateGenerateChallengeRequest(array $requestAll): array;

    /**
     * Validates the request data for verifying a challenge.
     */
    public static function validateVerifyChallengeRequest(array $requestAll): array;

    /**
     * Generates a challenge for the verification method.
     *
     * @return array{id: string, string: string}
     */
    public static function generateChallenge(array $data): array;

    /**
     * Verifies a challenge for the verification method.
     */
    public static function verifyChallenge(array $data): bool;
}
