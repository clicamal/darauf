<?php

declare(strict_types=1);

use Clicamal\Darauf\Darauf;

it('resolves the singleton', function () {
    expect(app(Darauf::class))->toBeInstanceOf(Darauf::class);
});

it('returns the same instance from the container', function () {
    expect(app(Darauf::class))->toBe(app(Darauf::class));
});

it('merges the package config', function () {
    expect(config('darauf.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('darauf::messages.placeholder'))->toBe('Darauf placeholder translation.');
});
