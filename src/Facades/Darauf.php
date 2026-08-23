<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Clicamal\Darauf\Darauf
 */
class Darauf extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Clicamal\Darauf\Darauf::class;
    }
}
