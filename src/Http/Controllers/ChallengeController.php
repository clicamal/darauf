<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers;

use Clicamal\Darauf\Darauf;
use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Exceptions\VerificationFailedException;
use Clicamal\Darauf\Exceptions\VerificationMethodNotSupportedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChallengeController extends Controller
{
    /**
     * Generates a new challenge for the specified verification method.
     */
    public function generateChallenge(Request $request, string $method): JsonResponse
    {
        $verificationMethod = Darauf::CHALLENGE_VERIFIERS[$method] ?? null;

        try {
            if ($verificationMethod === null) {
                throw new VerificationMethodNotSupportedException;
            }

            $data = $verificationMethod::validateGenerateChallengeRequest($request->all());

            $challenge = $verificationMethod::generateChallenge($data);

            return response()->json($challenge, 201);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Verifies a challenge for the specified verification method.
     */
    public function verifyChallenge(Request $request, string $method): JsonResponse
    {
        $verificationMethod = Darauf::CHALLENGE_VERIFIERS[$method] ?? null;

        try {
            if ($verificationMethod === null) {
                throw new VerificationMethodNotSupportedException;
            }

            $data = $verificationMethod::validateVerifyChallengeRequest($request->all());

            if (! $verificationMethod::verifyChallenge($data)) {
                throw new VerificationFailedException;
            }

            return response()->json([
                'message' => __('darauf::messages.success.did_subject_authenticated'),
            ]);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
