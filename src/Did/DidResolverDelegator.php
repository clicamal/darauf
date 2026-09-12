<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Did;

use Clicamal\Darauf\Exceptions\DidResolutionException;
use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
use Illuminate\Contracts\Container\Container;

/**
 * Delegates DID resolution and dereferencing to the resolver registered
 * for the DID method parsed from the input.
 */
class DidResolverDelegator implements DidResolverContract
{
    public function __construct(private readonly Container $container) {}

    public function resolve(DidDomainModel $did): ?DidDocument
    {
        return $this->resolverFor($did->getMethodName())->resolve($did);
    }

    public function dereference(DidUrlDomainModel $didUrl): VerificationMethod|Authentication|null
    {
        return $this->resolverFor($didUrl->getDid()->getMethodName())->dereference($didUrl);
    }

    private function resolverFor(string $method): DidResolverContract
    {
        $key = "darauf.didResolvers.{$method}";

        if (! $this->container->bound($key)) {
            throw new DidResolutionException('method_not_supported');
        }

        return $this->container->make($key);
    }
}
