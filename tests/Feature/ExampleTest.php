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
    expect(config('darauf.challengeManagers'))->toBeArray()
        ->and(config('darauf.challengeManagers'))->toHaveKey('Multikey|Ed25519VerificationKey2020');
});

it('loads the package translations', function () {
    expect(trans('darauf::messages.error.challenge_verification_failed'))->toBe('Challenge verification failed.');
});
