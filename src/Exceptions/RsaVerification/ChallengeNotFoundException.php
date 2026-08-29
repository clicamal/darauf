<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions\RsaVerification;

use Clicamal\Darauf\Exceptions\DaraufException;

class ChallengeNotFoundException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::messages.error.rsa_verification.challenge_not_found'));
    }
}
