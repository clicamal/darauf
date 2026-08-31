<?php

declare(strict_types=1);

namespace Clicamal\Darauf\VerificationMethods\RSA\Exceptions;

use Clicamal\Darauf\Exceptions\DaraufException;

class RsaVerificationMethodNotFoundException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::verification_methods.rsa.rsa_verification_method_not_found'));
    }
}
