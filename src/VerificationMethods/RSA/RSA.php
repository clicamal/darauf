<?php

declare(strict_types=1);

namespace Clicamal\Darauf\VerificationMethods\RSA;

use Clicamal\Darauf\Exceptions\DidDocumentNotFoundException;
use Clicamal\Darauf\Exceptions\InvalidDidException;
use Clicamal\Darauf\Helpers\DidHelper;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\VerificationMethods\ChallengeVerifierContract;
use Clicamal\Darauf\VerificationMethods\RSA\Exceptions\ChallengeNotFoundException;
use Clicamal\Darauf\VerificationMethods\RSA\Exceptions\InvalidPublicKeyException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RSA implements ChallengeVerifierContract
{
    /**
     * @param  array<string, mixed>  $requestAll
     * @return array<string, mixed>
     */
    public static function validateGenerateChallengeRequest(array $requestAll): array
    {
        return Validator::make($requestAll, [
            'didDocumentId' => 'required|string|max:100',
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $requestAll
     * @return array<string, mixed>
     */
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
     * @param  array<string, mixed>  $data
     * @return array{id: string, string: string}
     */
    public static function generateChallenge(array $data): array
    {
        $id = Str::uuid()->toString();

        $didDocumentId = $data['didDocumentId'];

        if (! DidHelper::validateDid($didDocumentId)) {
            throw new InvalidDidException;
        }

        $didDocument = DidDocument::where('did_document_id', $didDocumentId)->first();

        if (! $didDocument) {
            throw new DidDocumentNotFoundException;
        }

        $rsaVerificationMethod = $didDocument
            ->verificationMethods()
            ->whereJsonContains('serialized->type', 'RSA')
            ->first();

        if (! $rsaVerificationMethod) {
            throw new ChallengeNotFoundException;
        }

        $string = Str::random(32);

        $verificationMethod = json_decode($rsaVerificationMethod->serialized, true);

        $publicKey = $verificationMethod['publicKeyMultibase'];

        Cache::put(
            "darauf_rsa_challenge:{$id}",
            [
                'string' => $string,
                'publicKey' => $publicKey,
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

        if ($der === false) {
            throw new InvalidPublicKeyException;
        }

        return "-----BEGIN PUBLIC KEY-----\n".wordwrap(base64_encode($der), 64, "\n", true)."\n-----END PUBLIC KEY-----";
    }
}
