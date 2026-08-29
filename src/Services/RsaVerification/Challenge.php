<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Services\RsaVerification;

use Cache;
use Clicamal\Darauf\Exceptions\ChallengeNotFoundException;
use Str;

class Challenge
{
    public static function generate(string $publicKey): array
    {
        $challengeId = Str::uuid()->toString();
        $challengeString = Str::random(32);

        Cache::put(
            "darauf_rsa_challenge:{$challengeId}",
            [
                'challengeString' => $challengeString,
                'publicKey' => $publicKey,
            ],
            now()->addMinutes(5),
        );

        return [
            'challengeId' => $challengeId,
            'challengeString' => $challengeString,
        ];
    }

    public static function verify(string $challengeId, string $signature): int|bool
    {
        $challenge = Cache::pull("darauf_rsa_challenge:{$challengeId}");

        if ($challenge === null) {
            throw new ChallengeNotFoundException;
        }

        return openssl_verify($challenge['challengeString'], $signature, $challenge['publicKey']);
    }
}
