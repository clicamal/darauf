<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Helpers;

use Clicamal\Darauf\Exceptions\InvalidDidException;
use Clicamal\Darauf\Exceptions\InvalidDidWebIdException;
use Clicamal\Darauf\Exceptions\InvalidDidWebPathException;
use Closure;
use Illuminate\Support\Facades\Http;
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

            '@context' => [
                'sometimes',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (is_string($value)) {
                        return;
                    }

                    if (is_array($value)) {
                        foreach ($value as $context) {
                            if (! is_string($context)) {
                                $fail('The '.$attribute.' must be a string or an array of strings.');
                            }
                        }

                        return;
                    }

                    $fail('The '.$attribute.' must be a string or an array of strings.');
                },
            ],

            'alsoKnownAs' => ['sometimes', 'array'],
            'alsoknownAs.*' => ['required_with:alsoKnownAs.*', 'string'],

            'controller' => [
                'sometimes',
                function (string $attribute, mixed $value, Closure $fail) {
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
                },
            ],

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
            'service.*.type' => [
                function (string $attribute, mixed $value, Closure $fail) {
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
                },
            ],
            'service.*.serviceEndpoint' => [
                function (string $attribute, mixed $value, Closure $fail) {
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
                },
            ],
        ])->validate();

        return $validatedDidDocument;
    }

    /**
     * Checks if a given DID is a DID Web identifier by checking if it starts with "did:web:".
     */
    public static function isDidWeb(string $did): bool
    {
        return str_starts_with($did, 'did:web:');
    }

    /**
     * Converts a DID web identifier to its canonical URL for fetching the DID document.
     *
     * @throws InvalidDidWebIdException
     */
    public static function didWebIdToCanonicalUrl(string $didWebId): string
    {
        $didPath = explode(':', $didWebId);

        if (count($didPath) < 3) {
            throw new InvalidDidWebIdException;
        }

        $didDocumentPath = count($didPath) === 3 ?
            'https://'.implode('/', array_slice($didPath, 2)).'/.well-known/did.json' :
            'https://'.implode('/', array_slice($didPath, 2)).'/did.json';

        return str_replace('%3A', ':', $didDocumentPath);
    }

    /**
     * Converts a DID web identifier to the URL on the local Darauf server.
     *
     * @throws InvalidDidWebIdException
     */
    public static function didWebIdToDaraufUrl(string $didWebId): string
    {
        $didPath = explode(':', $didWebId);

        if (count($didPath) < 3) {
            throw new InvalidDidWebIdException;
        }

        $authoritySegments = array_slice($didPath, 2);
        $host = $authoritySegments[0];
        $pathSegments = array_slice($authoritySegments, 1);

        $didDocumentPath = $pathSegments === [] ?
            'https://'.$host.'/.well-known/did.json' :
            'https://'.$host.'/diddocument/'.implode('/', $pathSegments).'/did.json';

        return str_replace('%3A', ':', $didDocumentPath);
    }

    /**
     * Converts a DID web resolution path back to a "did:web:" identifier.
     */
    public static function didWebPathToId(string $didWebPath): string
    {
        $didPath = explode('/', $didWebPath);

        if (count($didPath) < 2) {
            throw new InvalidDidWebPathException;
        }

        $last = end($didPath);

        if ($last !== 'did.json') {
            throw new InvalidDidWebPathException;
        }

        $segments = array_slice($didPath, 0, -1);

        if (count($segments) === 1) {
            $didWebId = 'did:web:'.$segments[0];
        } elseif ($segments[1] === '.well-known') {
            $didWebId = 'did:web:'.$segments[0];
        } elseif ($segments[1] === 'diddocument') {
            $didWebId = 'did:web:'.implode(':', array_merge([$segments[0]], array_slice($segments, 2)));
        } else {
            $didWebId = 'did:web:'.str_replace('/', ':', implode('/', $segments));
        }

        return $didWebId;
    }

    /**
     * Validates a DID web host segment (domain with optional port).
     */
    public static function validateDidWebHost(string $host): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)*(:[0-9]+)?$/',
            $host,
        ) === 1;
    }

    /**
     * Validates a DID web resolution path (e.g. "example.com/.well-known/did.json"
     * or "example.com/user/alice/did.json").
     */
    public static function validateDidWebPath(string $didWebPath): bool
    {
        $didPath = explode('/', $didWebPath);

        if (count($didPath) < 2) {
            return false;
        }

        $host = $didPath[0];

        if (! self::validateDidWebHost($host)) {
            return false;
        }

        $last = end($didPath);

        if ($last !== 'did.json') {
            return false;
        }

        if (in_array('.well-known', $didPath, true) && ($didPath[1] !== '.well-known' || count($didPath) !== 3)) {
            return false;
        }

        return true;
    }

    /**
     * Validates a DID web document by fetching the published document from its
     * canonical URL and comparing it against the submitted data.
     *
     * @param  array<string, mixed>  $didDocumentData
     */
    public static function validateDidWebDocument(array $didDocumentData): bool
    {
        try {
            $didDocumentUrl = self::didWebIdToCanonicalUrl($didDocumentData['id']);
        } catch (InvalidDidWebIdException) {
            return false;
        }

        $host = parse_url($didDocumentUrl, PHP_URL_HOST);

        if (! is_string($host) || ! self::validateDidWebHost($host) || self::isPrivateHost($host)) {
            return false;
        }

        $fetchedDidDocument = Http::timeout(10)->get($didDocumentUrl)->json();

        if (! is_array($fetchedDidDocument)) {
            return false;
        }

        return $didDocumentData == $fetchedDidDocument;
    }

    private static function isPrivateHost(string $host): bool
    {
        $host = strtolower($host);

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return in_array($host, ['localhost', 'localhost.localdomain'], true);
    }
}
