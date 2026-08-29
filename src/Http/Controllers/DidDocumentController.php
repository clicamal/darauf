<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers;

use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Exceptions\RsaVerification\InvalidPublicKeyException;
use Clicamal\Darauf\Exceptions\UsernameTakenException;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
use Clicamal\Darauf\Services\DidGenerator;
use Clicamal\Darauf\Services\RsaVerification\PublicKeyValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DidDocumentController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string|max:30',
            'publicKey' => 'required|string|max:451',
        ]);

        try {
            if (! PublicKeyValidator::validate($data['publicKey'])) {
                throw new InvalidPublicKeyException;
            }

            $did = DidGenerator::generate($data['username']);

            if (DidDocument::where('did', $did)->exists()) {
                throw new UsernameTakenException;
            }

            $didDocument = DidDocument::create([
                'did' => $did,
            ]);

            $verificationMethod = VerificationMethod::create([
                'id' => $did.'#key1',
                'controller' => $did,
                'type' => 'RSA',
                'public_key' => $data['publicKey'],
            ]);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => __('darauf::messages.success.create_did_document'),
        ], 201);
    }
}
