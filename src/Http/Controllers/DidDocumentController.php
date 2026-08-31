<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers;

use Clicamal\Darauf\Darauf;
use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Models\DidDocument;
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

        $username = $data['username'];
        $publicKey = $data['publicKey'];

        try {
            $did = DidDocument::generateSha256DidFromUsername($username);

            Darauf::createDidDocument(['did' => $did], ['publicKeyMultibase' => $publicKey]);
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
