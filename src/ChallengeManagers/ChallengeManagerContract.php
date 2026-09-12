<?php

declare(strict_types=1);

namespace Clicamal\Darauf\ChallengeManagers;

use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\VerificationMethod;

interface ChallengeManagerContract
{
    /**
     * Verifies a challenge signature against the challenge data.
     */
    public function verify(string $signature, string $nonce, VerificationMethod|Authentication $resource): bool;
}
