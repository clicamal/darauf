<?php

declare(strict_types=1);

namespace Clicamal\Darauf\VerificationMethods\RSA\Exceptions;

use Clicamal\Darauf\Exceptions\DaraufException;

class ChallengeNotFoundException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::verification_methods.rsa.challenge_not_found'));
    }
}
