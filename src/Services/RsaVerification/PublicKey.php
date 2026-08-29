<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Services\RsaVerification;

use OpenSSLAsymmetricKey;

class PublicKey
{
    public static function validate(string $key): OpenSSLAsymmetricKey|false
    {
        return openssl_pkey_get_public($key);
    }
}
