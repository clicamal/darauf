<?php

declare(strict_types=1);

namespace Clicamal\Darauf\ChallengeManagers\Ed25519\Exceptions;

use Clicamal\Darauf\Exceptions\DaraufException;

class VerificationMethodNotFoundException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::challenge_managers.ed25519.verification_method_not_found'));
    }
}
