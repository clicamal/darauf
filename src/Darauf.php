<?php

declare(strict_types=1);

namespace Clicamal\Darauf;

use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
use Clicamal\Darauf\VerificationMethods\ChallengeVerifierContract;
use Clicamal\Darauf\VerificationMethods\RSA\Exceptions\InvalidPublicKeyException;
use Clicamal\Darauf\VerificationMethods\RSA\RSA;

class Darauf
{
    /**
     * All supported verification methods. Feel free to add your own created methods.
     * At least one is required. The first one is the default for new DID documents.
     *
     * To create a verification method simply create a class that implements the
     * ChallengeVerifierContract contract and add it to this array, with its respective method.
     *
     * @var array<string, ChallengeVerifierContract>
     */
    public const array CHALLENGE_VERIFIERS = [
        'RSA' => RSA::class,
    ];

    /**
     * Creates a DID document with a default verification method.
     *
     * @param  array{did: string}  $didDocumentData
     * @param array{
     *      id?: string,
     *      controller?: string,
     *      type?: string,
     *      publicKeyMultibase?: string,
     *      publicKeyJwk?: array<string, string>
     * } $verificationMethodData
     *
     * @throws InvalidPublicKeyException
     */
    public static function createDidDocument(array $didDocumentData, array $verificationMethodData): DidDocument
    {
        $publicKey = $verificationMethodData['publicKeyMultibase'] ?? $verificationMethodData['publicKeyJwk'] ?? null;

        $defaultChallengeVerifier = array_values(self::CHALLENGE_VERIFIERS)[0];

        if (! $defaultChallengeVerifier::validatePublicKey($publicKey)) {
            throw new InvalidPublicKeyException;
        }

        $didDocument = DidDocument::create($didDocumentData);

        $verificationMethodCreateData = [
            'did_document_did' => $didDocument->did,
            'id' => (string) $didDocument->did.'#key1',
            'controller' => $didDocument->did,
            'type' => $defaultChallengeVerifier::getVerificationType(),
            ...$verificationMethodData,
        ];

        VerificationMethod::create($verificationMethodCreateData);

        return $didDocument;
    }
}
