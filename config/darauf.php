<?php

declare(strict_types=1);
use Clicamal\Darauf\ChallengeManagers\Ed25519\Ed25519ChallengeManager;
use Clicamal\Darauf\Did\Resolvers\DidWebResolver;

return [
    'didResolvers' => [
        'web' => DidWebResolver::class,
    ],
    'challengeManagers' => [
        'Multikey|Ed25519VerificationKey2020' => Ed25519ChallengeManager::class,
    ],
];
