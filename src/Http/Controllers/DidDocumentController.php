<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers;

use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Models\DidDocument;
use Exception;
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
        $data = $request->validate([
            'id' => ['required', 'string'],
        ]);

        try {
            DidDocument::create([
                'did_document_id' => $data['id'],
                'serialized' => json_encode($request->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);

            return response()->json([
                'message' => __('darauf::messages.success.did_document_registered'),
            ], 201);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Exception $exception) {
            return response()->json([
                'message' => __('darauf::messages.error.general'),
            ], 422);
        }
    }
}
