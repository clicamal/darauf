<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions;

class InvalidPublicKeyException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::messages.error.invalid_public_key'));
    }
}
