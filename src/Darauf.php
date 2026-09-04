<?php

declare(strict_types=1);

namespace Clicamal\Darauf;

use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
use Clicamal\Darauf\VerificationMethods\ChallengeVerifierContract;
use Clicamal\Darauf\VerificationMethods\RSA\Exceptions\InvalidPublicKeyException;
use Clicamal\Darauf\VerificationMethods\RSA\RSA;

class Darauf
{
    /**
     * All the challenge verifiers available in the system, mapped by their names.
     * Feel free to add more challenge verifiers to this array as needed.
     *
     * To add a new challenge verifier, create a class that implements the
     * ChallengeVerifierContract interface and add it to this array with a unique name.
     *
     * @var array<string, ChallengeVerifierContract>
     */
    public const array CHALLENGE_VERIFIERS = [
        'RSA' => RSA::class,
    ];

    /**
     * Creates a new DID document and its associated verification methods in the database.
     *
     * @param  array{did: string}  $didDocumentData
     */
    public static function createDidDocument(array $didDocumentData): DidDocument
    {
        $didDocumentId = $didDocumentData['id'];

        $verificationMethods = $didDocumentData['verificationMethod'];
        $serializedVerificationMethods = [];

        foreach ($verificationMethods as $verificationMethodData) {
            $serializedVerificationMethod = json_encode($verificationMethodData);

            if ($serializedVerificationMethod === false) {
                throw new DaraufException('Failed to serialize verification method data.');
            }

            $serializedVerificationMethods[] = ['id' => $verificationMethodData['id'], 'serialized' => $serializedVerificationMethod];
        }

        unset($didDocumentData['verificationMethod']);

        $serializedDidDocument = json_encode($didDocumentData);

        $didDocument = DidDocument::create([
            'did_document_id' => $didDocumentId,
            'serialized' => $serializedDidDocument,
        ]);

        foreach ($serializedVerificationMethods as $serializedVerificationMethod) {
            VerificationMethod::create([
                'verification_method_id' => $serializedVerificationMethod['id'],
                'did_document_id' => $didDocument->id,
                'serialized' => $serializedVerificationMethod['serialized'],
            ]);
        }

        if ($serializedDidDocument === false) {
            throw new DaraufException('Failed to serialize DID document data.');
        }

        return $didDocument;
    }
}
