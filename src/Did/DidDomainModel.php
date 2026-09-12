<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Did;

use Clicamal\Darauf\Exceptions\ValidationException;

/**
 * Represents a Decentralized Identifier (DID) and provides methods for validation and retrieval of its components.
 */
class DidDomainModel
{
    /**
     * Regular expression pattern for validating a DID.
     */
    public const string DID_REGEX = '/^did:[a-z0-9]+:(?:[A-Za-z0-9._-]|%[0-9A-Fa-f]{2})+(?::(?:[A-Za-z0-9._-]|%[0-9A-Fa-f]{2})+)*$/';

    /**
     * The method name of the DID (e.g., "web", "key").
     */
    private string $methodName;

    /**
     * The method-specific ID of the DID.
     */
    private string $methodSpecificId;

    public function __construct(string $did)
    {
        if (! self::validate($did)) {
            throw new ValidationException('invalid_did');
        }

        $parts = explode(':', $did);

        $this->methodName = $parts[1];
        $this->methodSpecificId = implode(':', array_slice($parts, 2));
    }

    /**
     * Validates a DID string.
     */
    public static function validate(string $did): bool
    {
        return preg_match(
            self::DID_REGEX,
            $did,
        ) === 1;
    }

    /**
     * Returns the method name of the DID.
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * Returns the method-specific ID of the DID.
     */
    public function getMethodSpecificId(): string
    {
        return $this->methodSpecificId;
    }

    /**
     * Returns the full DID string.
     */
    public function getFull(): string
    {
        return "did:{$this->methodName}:{$this->methodSpecificId}";
    }
}
