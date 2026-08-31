<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions;

class VerificationFailedException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::messages.error.verification_failed'));
    }
}
