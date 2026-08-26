<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions;

use Clicamal\Darauf\Exceptions\DaraufException;

class UsernameTakenException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::messages.error.username_taken'));
    }
}
