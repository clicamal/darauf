<?php

declare(strict_types=1);

namespace Clicamal\Darauf\VerificationMethods\RSA;

use Cache;
use Clicamal\Darauf\Exceptions\DidDocumentNotFoundException;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\VerificationMethods\ChallengeVerifierContract;
use Clicamal\Darauf\VerificationMethods\RSA\Exceptions\ChallengeNotFoundException;
use Illuminate\Support\Facades\Validator;
use Str;

class RSA implements ChallengeVerifierContract
{
    public static function getVerificationType(): string
    {
        return 'RSA';
    }

    public static function validateGenerateChallengeRequest(array $requestAll): array
    {
        return Validator::make($requestAll, [
            'username' => 'required|string|max:30',
        ])->validate();
    }

    public static function validateVerifyChallengeRequest(array $requestAll): array
    {
        return Validator::make($requestAll, [
            'challengeId' => 'required|string|max:100',
            'signature' => 'required|string|max:512',
        ])->validate();
    }

    /**
     * Generates a RSA challenge
     *
     * @return array{id: string, string: string}
     */
    public static function generateChallenge(array $data): array
    {
        $id = Str::uuid()->toString();

        $username = $data['username'];

        $did = DidDocument::generateSha256DidFromUsername($username);
        $didDocument = DidDocument::where('did', $did)->first();

        if (! $didDocument) {
            throw new DidDocumentNotFoundException;
        }

        $rsaVerificationMethod = $didDocument->verificationMethods()->where('type', self::getVerificationType())->first();

        if (! $rsaVerificationMethod) {
            throw new ChallengeNotFoundException;
        }

        $string = Str::random(32);

        Cache::put(
            "darauf_rsa_challenge:{$id}",
            [
                'string' => $string,
                'publicKey' => $rsaVerificationMethod->publicKeyMultibase,
            ],
            now()->addMinutes(5),
        );

        return [
            'id' => $id,
            'string' => $string,
        ];
    }

    /**
     * Verifies a RSA challenge verifying a signature against its public key.
     *
     * @param  array{challengeId: string, signature: string}  $data
     *
     * @throws ChallengeNotFoundException
     */
    public static function verifyChallenge(array $data): bool
    {
        $challengeId = $data['challengeId'];
        $signature = base64_decode($data['signature']);

        $challenge = Cache::pull("darauf_rsa_challenge:{$challengeId}");

        if ($challenge === null) {
            throw new ChallengeNotFoundException;
        }

        return (bool) openssl_verify($challenge['string'], $signature, self::toPem($challenge['publicKey']));
    }

    /**
     * Converts a multibase public key to a PEM public key.
     */
    private static function toPem(string $multibaseKey): string
    {
        $der = base64_decode(strtr(substr($multibaseKey, 1), '-_', '+/'), true);

        return "-----BEGIN PUBLIC KEY-----\n".wordwrap(base64_encode($der), 64, "\n", true)."\n-----END PUBLIC KEY-----";
    }

    public static function validatePublicKey(string|array $publicKey): bool
    {
        return match (true) {
            is_array($publicKey) => self::validateJwk($publicKey),
            self::isMultibase($publicKey) => self::validateMultibaseKey($publicKey),
            default => false,
        };
    }

    /**
     * Verifies if a key is multibase.
     */
    public static function isMultibase(string $key): bool
    {
        return str_starts_with($key, 'u');
    }

    /**
     * Validates a RSA Json Web Key.
     */
    public static function validateJwk(array $key): bool
    {
        if (! isset($key['kty'], $key['n'], $key['e'])) {
            return false;
        }

        if ($key['kty'] !== 'RSA') {
            return false;
        }

        $base64 = strtr($key['n'], '-_', '+/');

        $binModule = base64_decode($base64, true);

        if ($binModule === false) {
            return false;
        }

        $byteSize = strlen($binModule);

        return $byteSize === 256 || $byteSize === 257;
    }

    /**
     * Validates a multibase key.
     */
    public static function validateMultibaseKey(string $key): bool
    {
        $base64 = strtr(substr($key, 1), '-_', '+/');

        $binKey = base64_decode($base64, true);

        if ($binKey === false) {
            return false;
        }

        $byteSize = strlen($binKey);

        return $byteSize >= 260 && $byteSize <= 300;
    }
}
