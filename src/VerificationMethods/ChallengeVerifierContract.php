<?php

declare(strict_types=1);

namespace Clicamal\Darauf\VerificationMethods;

interface ChallengeVerifierContract
{
    /**
     * Returns the verification method type.
     */
    public static function getVerificationType(): string;

    /**
     * Validates a request body before passing it to the generateChallenge method.
     */
    public static function validateGenerateChallengeRequest(array $requestAll): array;

    /**
     * Validates a request body before passing it to the verifyChallenge method.
     */
    public static function validateVerifyChallengeRequest(array $requestAll): array;

    /**
     * Generates a challenge for the verification method.
     *
     * @return bool
     */
    public static function generateChallenge(array $data): array;

    /**
     * Verifies a challenge for the verification method.
     */
    public static function verifyChallenge(array $data): bool;

    /**
     * Validates a public key.
     */
    public static function validatePublicKey(string|array $publicKey): bool;
}
