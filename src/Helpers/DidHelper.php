<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Helpers;

use Clicamal\Darauf\Exceptions\InvalidDidException;
use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

abstract class DidHelper
{
    public const string DID_PATTERN = '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/';

    /**
     * Generates a new DID using a random string and SHA-256 hashing.
     * The generated DID is in the format "did:darauf:<hash>".
     * Returns the generated DID as a string.
     */
    public static function generateDid(): string
    {
        $randomString = Str::random(32);
        $randomStringHash = hash('sha256', $randomString);

        $did = (string) 'did:darauf:'.$randomStringHash;

        if (! self::validateDid($did)) {
            throw new InvalidDidException;
        }

        return $did;
    }

    /**
     * Validates a DID against the W3C DID specification using a regular expression.
     * Returns 1 if the DID is valid, 0 if it is invalid, or false if an error occurred during validation.
     */
    public static function validateDid(string $did): int|bool
    {
        return preg_match(self::DID_PATTERN, $did);
    }

    /**
     * Uses a Laravel validator to validate a DID document according to the W3C DID specification.
     * Returns the validated DID document as an array if it passes validation, or throws a ValidationException if it fails.
     *
     * @param  array<string, mixed>  $didDocument
     * @return array<string, mixed>
     */
    public static function validateDidDocument(array $didDocument): array
    {
        $validatedDidDocument = Validator::make($didDocument, [
            'id' => ['required', 'string'],

            'alsoKnownAs' => ['sometimes', 'array'],
            'alsoknownAs.*' => ['required_with:alsoKnownAs.*', 'string'],

            'controller' => ['sometimes', function (string $attribute, mixed $value, Closure $fail) {
                if (is_string($value)) {
                    return;
                }

                if (is_array($value) && count($value) > 0) {
                    foreach ($value as $controller) {
                        if (! is_string($controller)) {
                            $fail('The '.$attribute.' must be a string or an array of strings.');
                        }
                    }
                }
            }],

            'verificationMethod' => ['array'],
            'verificationMethod.*.id' => ['required_with:verificationMethod', 'string'],
            'verificationMethod.*.controller' => ['required_with:verificationMethod', 'string'],
            'verificationMethod.*.type' => ['required_with:verificationMethod', 'string'],
            'verificationMethod.*.publicKeyMultibase' => ['string'],
            'verificationMethod.*.publicKeyJwk' => ['array'],
            'verificationMethod.*.publicKeyJwk.kty' => ['string'],
            'verificationMethod.*.publicKeyJwk.use' => ['string'],
            'verificationMethod.*.publicKeyJwk.sig' => ['string'],
            'verificationMethod.*.publicKeyJwk.enc' => ['string'],
            'verificationMethod.*.publicKeyJwk.key_ops' => ['string'],
            'verificationMethod.*.publicKeyJwk.alg' => ['string'],
            'verificationMethod.*.publicKeyJwk.kid' => ['string'],
            'verificationMethod.*.publicKeyJwk.x5u' => ['string'],
            'verificationMethod.*.publicKeyJwk.x5c' => ['array'],
            'verificationMethod.*.publicKeyJwk.x5t' => ['string'],
            'verificationMethod.*.publicKeyJwk.x5t#S256' => ['string'],

            'authentication' => ['array'],
            'authentication.*.id' => ['string'],
            'authentication.*.controller' => ['string'],
            'authentication.*.type' => ['string'],
            'authentication.*.publicKeyMultibase' => ['string'],
            'authentication.*.publicKeyJwk' => ['array'],
            'authentication.*.publicKeyJwk.kty' => ['string'],
            'authentication.*.publicKeyJwk.use' => ['string'],
            'authentication.*.publicKeyJwk.sig' => ['string'],
            'authentication.*.publicKeyJwk.enc' => ['string'],
            'authentication.*.publicKeyJwk.key_ops' => ['string'],
            'authentication.*.publicKeyJwk.alg' => ['string'],
            'authentication.*.publicKeyJwk.kid' => ['string'],
            'authentication.*.publicKeyJwk.x5u' => ['string'],
            'authentication.*.publicKeyJwk.x5c' => ['array'],
            'authentication.*.publicKeyJwk.x5t' => ['string'],
            'authentication.*.publicKeyJwk.x5t#S256' => ['string'],

            'assertionMethod' => ['array'],
            'assertionMethod.*.id' => ['string'],
            'assertionMethod.*.controller' => ['string'],
            'assertionMethod.*.type' => ['string'],
            'assertionMethod.*.publicKeyMultibase' => ['string'],
            'assertionMethod.*.publicKeyJwk' => ['array'],
            'assertionMethod.*.publicKeyJwk.kty' => ['string'],
            'assertionMethod.*.publicKeyJwk.use' => ['string'],
            'assertionMethod.*.publicKeyJwk.sig' => ['string'],
            'assertionMethod.*.publicKeyJwk.enc' => ['string'],
            'assertionMethod.*.publicKeyJwk.key_ops' => ['string'],
            'assertionMethod.*.publicKeyJwk.alg' => ['string'],
            'assertionMethod.*.publicKeyJwk.kid' => ['string'],
            'assertionMethod.*.publicKeyJwk.x5u' => ['string'],
            'assertionMethod.*.publicKeyJwk.x5c' => ['array'],
            'assertionMethod.*.publicKeyJwk.x5t' => ['string'],
            'assertionMethod.*.publicKeyJwk.x5t#S256' => ['string'],

            'keyAgreement' => ['array'],
            'keyAgreement.*.id' => ['string'],
            'keyAgreement.*.controller' => ['string'],
            'keyAgreement.*.type' => ['string'],
            'keyAgreement.*.publicKeyMultibase' => ['string'],
            'keyAgreement.*.publicKeyJwk' => ['array'],
            'keyAgreement.*.publicKeyJwk.kty' => ['string'],
            'keyAgreement.*.publicKeyJwk.use' => ['string'],
            'keyAgreement.*.publicKeyJwk.sig' => ['string'],
            'keyAgreement.*.publicKeyJwk.enc' => ['string'],
            'keyAgreement.*.publicKeyJwk.key_ops' => ['string'],
            'keyAgreement.*.publicKeyJwk.alg' => ['string'],
            'keyAgreement.*.publicKeyJwk.kid' => ['string'],
            'keyAgreement.*.publicKeyJwk.x5u' => ['string'],
            'keyAgreement.*.publicKeyJwk.x5c' => ['array'],
            'keyAgreement.*.publicKeyJwk.x5t' => ['string'],
            'keyAgreement.*.publicKeyJwk.x5t#S256' => ['string'],

            'capabilityInvocation' => ['array'],
            'capabilityInvocation.*.id' => ['string'],
            'capabilityInvocation.*.controller' => ['string'],
            'capabilityInvocation.*.type' => ['string'],
            'capabilityInvocation.*.publicKeyMultibase' => ['string'],
            'capabilityInvocation.*.publicKeyJwk' => ['array'],
            'capabilityInvocation.*.publicKeyJwk.kty' => ['string'],
            'capabilityInvocation.*.publicKeyJwk.use' => ['string'],
            'capabilityInvocation.*.publicKeyJwk.sig' => ['string'],
            'capabilityInvocation.*.publicKeyJwk.enc' => ['string'],
            'capabilityInvocation.*.publicKeyJwk.key_ops' => ['string'],
            'capabilityInvocation.*.publicKeyJwk.alg' => ['string'],
            'capabilityInvocation.*.publicKeyJwk.kid' => ['string'],
            'capabilityInvocation.*.publicKeyJwk.x5u' => ['string'],
            'capabilityInvocation.*.publicKeyJwk.x5c' => ['array'],
            'capabilityInvocation.*.publicKeyJwk.x5t' => ['string'],
            'capabilityInvocation.*.publicKeyJwk.x5t#S256' => ['string'],

            'capabilityDelegation' => ['array'],
            'capabilityDelegation.*.id' => ['string'],
            'capabilityDelegation.*.controller' => ['string'],
            'capabilityDelegation.*.type' => ['string'],
            'capabilityDelegation.*.publicKeyMultibase' => ['string'],
            'capabilityDelegation.*.publicKeyJwk' => ['array'],
            'capabilityDelegation.*.publicKeyJwk.kty' => ['string'],
            'capabilityDelegation.*.publicKeyJwk.use' => ['string'],
            'capabilityDelegation.*.publicKeyJwk.sig' => ['string'],
            'capabilityDelegation.*.publicKeyJwk.enc' => ['string'],
            'capabilityDelegation.*.publicKeyJwk.key_ops' => ['string'],
            'capabilityDelegation.*.publicKeyJwk.alg' => ['string'],
            'capabilityDelegation.*.publicKeyJwk.kid' => ['string'],
            'capabilityDelegation.*.publicKeyJwk.x5u' => ['string'],
            'capabilityDelegation.*.publicKeyJwk.x5c' => ['array'],
            'capabilityDelegation.*.publicKeyJwk.x5t' => ['string'],
            'capabilityDelegation.*.publicKeyJwk.x5t#S256' => ['string'],

            'service' => ['array'],
            'service.*.id' => ['required_with:service', 'string'],
            'service.*.type' => [function (string $attribute, mixed $value, Closure $fail) {
                if (is_string($value)) {
                    return;
                }

                if (is_array($value)) {
                    foreach ($value as $type) {
                        if (! is_string($type)) {
                            $fail('The '.$attribute.' must be a string or an array of strings.');
                        }
                    }
                }
            }],
            'service.*.serviceEndpoint' => [function (string $attribute, mixed $value, Closure $fail) {
                $isUri = static function (mixed $endpoint): bool {
                    return is_string($endpoint)
                        && $endpoint !== ''
                        && preg_match(
                            '/^[A-Za-z][A-Za-z0-9+.-]*:[^\s<>"{}|\\^`\[\]]+$/D',
                            $endpoint,
                        ) === 1;
                };

                $isValidEndpoint = function (mixed $endpoint) use (&$isValidEndpoint, $isUri): bool {
                    if ($isUri($endpoint)) {
                        return true;
                    }

                    if (! is_array($endpoint) || $endpoint === []) {
                        return false;
                    }

                    foreach ($endpoint as $member) {
                        if (! $isValidEndpoint($member)) {
                            return false;
                        }
                    }

                    return true;
                };

                if (! $isValidEndpoint($value)) {
                    $fail('The '.$attribute.' must be a non-empty URI string, map, or set containing URI strings and/or maps.');
                }
            }],
        ])->validate();

        return $validatedDidDocument;
    }
}
