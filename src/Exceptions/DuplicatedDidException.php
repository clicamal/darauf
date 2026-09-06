<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions;

class DuplicatedDidException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::messages.error.duplicated_did'));
    }
}
