<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Did;

/**
 * Validates the structure and format of DID documents and their components.
 */
abstract class DidValidator
{
    /**
     * Validates the structure and format of a DID Document.
     *
     * @param  array<string, mixed>  $didDocument
     */
    public static function validateDidDocument(array $didDocument): bool
    {
        if (! isset($didDocument['id']) || ! is_string($didDocument['id']) || ! DidDomainModel::validate($didDocument['id'])) {
            return false;
        }

        if (isset($didDocument['alsoKnownAs']) && ! self::validateUriSet($didDocument['alsoKnownAs'])) {
            return false;
        }

        if (isset($didDocument['controller']) && ! self::validateDidSet($didDocument['controller'])) {
            return false;
        }

        foreach (['verificationMethod', 'authentication', 'assertionMethod',
            'keyAgreement', 'capabilityInvocation', 'capabilityDelegation'] as $name) {
            if (! isset($didDocument[$name])) {
                continue;
            }

            if (! is_array($didDocument[$name])) {
                return false;
            }

            foreach ($didDocument[$name] as $item) {
                if (is_string($item) ? ! self::validateDidUrl($item)
                    : ! is_array($item) || ! self::validateVerificationMethod($item)) {
                    return false;
                }
            }
        }

        if (isset($didDocument['service'])) {
            if (! is_array($didDocument['service'])) {
                return false;
            }

            foreach ($didDocument['service'] as $service) {
                if (! is_array($service) || ! isset($service['id'], $service['type'], $service['serviceEndpoint'])
                    || ! is_string($service['id']) || ! self::validateUri($service['id'])
                    || ! self::validateStringSet($service['type'])
                    || ! self::validateEndpoint($service['serviceEndpoint'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates a DID URL according to the DID URL specification.
     */
    public static function validateDidUrl(string $value): bool
    {
        return (bool) preg_match('/^did:[a-z0-9]+:[A-Za-z0-9._:%-]+(?:[?#\/].*)?$/', $value);
    }

    /**
     * Validates a URI according to the URI specification.
     */
    public static function validateUri(string $value): bool
    {
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9+.-]*:[^\s]+$/', $value);
    }

    /**
     * Validates a set of URIs, ensuring that each URI is valid.
     */
    public static function validateUriSet(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_string($item) || ! self::validateUri($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a set of strings, ensuring that each string is non-empty.
     */
    public static function validateStringSet(mixed $value): bool
    {
        if (is_string($value)) {
            return $value !== '';
        }

        if (! is_array($value) || $value === []) {
            return false;
        }
        foreach ($value as $item) {
            if (! is_string($item) || $item === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a set of DIDs, ensuring that each DID is valid.
     */
    public static function validateDidSet(mixed $value): bool
    {
        if (is_string($value)) {
            return DidDomainModel::validate($value);
        }

        if (! is_array($value) || $value === []) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_string($item) || ! DidDomainModel::validate($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a verification method according to the DID specification.
     *
     * @param  array<string, mixed>  $value
     */
    public static function validateVerificationMethod(array $value): bool
    {
        return isset($value['id'], $value['controller'], $value['type'])
            && is_string($value['id']) && self::validateDidUrl($value['id'])
            && is_string($value['controller']) && self::validateDidSet($value['controller'])
            && is_string($value['type']) && $value['type'] !== ''
            && (! isset($value['publicKeyJwk']) || is_array($value['publicKeyJwk']))
            && (! isset($value['publicKeyMultibase'])
                || is_string($value['publicKeyMultibase']) && $value['publicKeyMultibase'] !== '');
    }

    /**
     * Validates a service endpoint according to the DID specification.
     */
    public static function validateEndpoint(mixed $value): bool
    {
        if (is_string($value)) {
            return self::validateUri($value);
        }

        if (! is_array($value) || $value === []) {
            return false;
        }
        foreach ($value as $item) {
            if (! self::validateEndpoint($item)) {
                return false;
            }
        }

        return true;
    }
}
