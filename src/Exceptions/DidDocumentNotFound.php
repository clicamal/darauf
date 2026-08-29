<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Exceptions;

class DidDocumentNotFound extends DaraufException
{
    public function __construct()
    {
        parent::__construct(__('darauf::messages.error.did_document_not_found'));
    }
}
