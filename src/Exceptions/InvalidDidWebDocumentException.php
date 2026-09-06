<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions;

class InvalidDidWebDocumentException extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::messages.error.invalid_did_web_document'));
    }
}
