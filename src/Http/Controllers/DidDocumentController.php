<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers;

use Clicamal\Darauf\Darauf;
use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Helpers\DidHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DidDocumentController extends Controller
{
    /**
     * Registers a new DID document sent by the client in the system.
     */
    public function register(Request $request): JsonResponse
    {
        $data = DidHelper::validateDidDocument($request->all());

        try {
            $didDocument = Darauf::createDidDocument($data);

            return response()->json([
                'did' => $didDocument->__get('did_document_id'),
            ], 201);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
