<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions;

class RsaVerificationFailedException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::messages.error.rsa_verification_failed'));
    }
}
