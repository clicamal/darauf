<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions;

class RsaVerificationMethodNotFound extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::messages.error.rsa_verification_method_not_found'));
    }
}
