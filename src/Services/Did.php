<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Services;

use Clicamal\Darauf\Exceptions\InvalidDidException;

class Did
{
    public const string DID_PATTERN = '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/';

    public static function generateSha256FromUsername(string $username): string
    {
        $usernameHash = hash('sha256', $username);

        $did = 'did:darauf:'.$usernameHash;

        if (! self::validate($did)) {
            throw new InvalidDidException;
        }

        return $did;
    }

    public static function validate(string $did): int|bool
    {
        return preg_match(self::DID_PATTERN, $did);
    }
}
