<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers;

use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;
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

        $did = 'did:darauf:'.hash('sha256', $data['username']);

        $didDocument = DidDocument::query()->create([
            'did' => $did,
        ]);

        $verificationMethod = VerificationMethod::query()->create([
            'id' => $did.'#key1',
            'controller' => $did,
            'type' => 'RSA',
            'public_key' => $data['publicKey'],
        ]);

        return response()->json([
            'message' => 'DID Document created.',
        ], 201);
    }
}
