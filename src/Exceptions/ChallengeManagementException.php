<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions;

class ChallengeManagementException extends DaraufException
{
    public function __construct(string $message)
    {
        parent::__construct((string) 'darauf::messages.error.'.$message);
    }
}
