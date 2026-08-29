<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers\RsaVerification;

use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Exceptions\DidDocumentNotFoundException;
use Clicamal\Darauf\Exceptions\RsaVerification\RsaVerificationFailedException;
use Clicamal\Darauf\Exceptions\RsaVerification\RsaVerificationMethodNotFoundException;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Services\Did;
use Clicamal\Darauf\Services\RsaVerification\RsaChallenge;
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
            $did = Did::generateSha256FromUsername($data['username']);

            $didDocument = DidDocument::where('did', $did)->first();

            if (! $didDocument) {
                throw new DidDocumentNotFoundException;
            }

            $verificationMethod = $didDocument->verificationMethods()->where('type', 'RSA')->first();

            if (! $verificationMethod) {
                throw new RsaVerificationMethodNotFoundException;
            }

            $challenge = RsaChallenge::generate($verificationMethod->public_key);

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

            if (! $signature || ! RsaChallenge::verify($data['challengeId'], $signature)) {
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
