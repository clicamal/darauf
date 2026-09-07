<?php

declare(strict_types=1);

namespace Clicamal\Darauf;

use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Exceptions\DuplicatedDidException;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class Darauf
{
    /**
     * Creates a new DID document and its associated verification methods in the database.
     *
     * @param  array<string, mixed>  $didDocumentData
     *
     * @throws DaraufException
     */
    public static function createDidDocument(array $didDocumentData): DidDocument
    {
        $didDocumentId = $didDocumentData['id'] ?? null;
        $verificationMethods = $didDocumentData['verificationMethod'] ?? [];

        if (! is_string($didDocumentId) || ! is_array($verificationMethods)) {
            throw new DaraufException('Invalid DID document data.');
        }

        $serializedVerificationMethods = [];

        foreach ($verificationMethods as $verificationMethodData) {
            $serializedVerificationMethod = json_encode($verificationMethodData);

            if ($serializedVerificationMethod === false) {
                throw new DaraufException('Failed to serialize verification method data.');
            }

            $serializedVerificationMethods[] = ['id' => $verificationMethodData['id'] ?? null, 'serialized' => $serializedVerificationMethod];
        }

        unset($didDocumentData['verificationMethod']);

        $serializedDidDocument = json_encode($didDocumentData);

        if ($serializedDidDocument === false) {
            throw new DaraufException('Failed to serialize DID document data.');
        }

        try {
            return DB::transaction(function () use ($didDocumentId, $serializedDidDocument, $serializedVerificationMethods): DidDocument {
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

                return $didDocument;
            });
        } catch (UniqueConstraintViolationException) {
            throw new DuplicatedDidException;
        }
    }
}
