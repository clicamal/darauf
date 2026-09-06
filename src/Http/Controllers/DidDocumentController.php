<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers;

use Clicamal\Darauf\Darauf;
use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Exceptions\DidDocumentNotFoundException;
use Clicamal\Darauf\Exceptions\InvalidDidWebDocumentException;
use Clicamal\Darauf\Exceptions\InvalidDidWebPathException;
use Clicamal\Darauf\Helpers\DidHelper;
use Clicamal\Darauf\Models\DidDocument;
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
            if (DidHelper::isDidWeb($data['id'])) {
                if (! DidHelper::validateDidWebDocument($request->all())) {
                    throw new InvalidDidWebDocumentException;
                }
            }

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

    public function getDidWebDocument(Request $request, string $path): JsonResponse
    {
        $didWebPath = $request->getHost().'/'.$path.'/did.json';

        try {
            if (! DidHelper::validateDidWebPath($didWebPath)) {
                throw new InvalidDidWebPathException;
            }

            $didWebId = DidHelper::didWebPathToId($didWebPath);

            $didDocument = DidDocument::where('did_document_id', $didWebId)->first();

            if (! $didDocument) {
                throw new DidDocumentNotFoundException;
            }

            return response()->json(json_decode($didDocument->serialized, true), 200);
        } catch (DaraufException $exception) {
            $status = $exception instanceof DidDocumentNotFoundException ? 404 : 422;

            return response()->json([
                'message' => $exception->getMessage(),
            ], $status);
        }
    }
}
