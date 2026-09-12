<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Did;

use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;

/**
 * Interface for resolving DIDs.
 */
interface DidResolverContract
{
    /**
     * Resolves a DID to its corresponding DID Document.
     */
    public function resolve(DidDomainModel $did): ?DidDocument;

    /**
     * Dereferences a DID URL to its corresponding Verification Method or Authentication.
     */
    public function dereference(DidUrlDomainModel $didUrl): VerificationMethod|Authentication|null;
}
