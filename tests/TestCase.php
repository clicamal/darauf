<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Tests;

use Clicamal\Darauf\DaraufServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DaraufServiceProvider::class,
        ];
    }
}
