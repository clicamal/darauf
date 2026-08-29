<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers\RsaVerification;

use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Exceptions\DidDocumentNotFound;
use Clicamal\Darauf\Exceptions\RsaVerificationFailedException;
use Clicamal\Darauf\Exceptions\RsaVerificationMethodNotFound;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Services\DidGenerator;
use Clicamal\Darauf\Services\RsaVerification\Challenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RsaVerificationController extends Controller
{
    public function generateChallenge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string|max:30',
        ]);

        try {
            $did = DidGenerator::generate($data['username']);

            $didDocument = DidDocument::where('did', $did)->first();

            if (! $didDocument) {
                throw new DidDocumentNotFound;
            }

            $verificationMethod = $didDocument->verificationMethods()->where('type', 'RSA')->first();

            if (! $verificationMethod) {
                throw new RsaVerificationMethodNotFound;
            }

            $challenge = Challenge::generate($verificationMethod->public_key);

            return response()->json([
                'challenge' => $challenge,
            ], 201);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challengeId' => 'required|string|max:100',
            'signature' => 'required|string|max:512',
        ]);

        try {
            $signature = base64_decode($data['signature'], true);

            if (! $signature || ! Challenge::verify($data['challengeId'], $signature)) {
                throw new RsaVerificationFailedException;
            }

            return response()->json([
                'message' => __('darauf::messages.success.did_subject_authenticated'),
            ]);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 401);
        }
    }
}
