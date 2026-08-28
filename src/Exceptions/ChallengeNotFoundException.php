<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions;

class ChallengeNotFoundException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::messages.error.challenge_not_found'));
    }
}
