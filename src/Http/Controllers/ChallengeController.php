<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers;

use Clicamal\Darauf\Darauf;
use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Exceptions\VerificationFailedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChallengeController extends Controller
{
    public function generateChallenge(Request $request, string $method): JsonResponse
    {
        $verificationMethod = Darauf::CHALLENGE_VERIFIERS[$method] ?? array_values(Darauf::CHALLENGE_VERIFIERS)[0];

        $data = $verificationMethod::validateGenerateChallengeRequest($request->all());

        try {
            $challenge = $verificationMethod::generateChallenge($data);

            return response()->json($challenge, 201);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function verifyChallenge(Request $request, string $method): JsonResponse
    {
        $verificationMethod = Darauf::CHALLENGE_VERIFIERS[$method] ?? array_values(Darauf::CHALLENGE_VERIFIERS)[0];

        $data = $verificationMethod::validateVerifyChallengeRequest($request->all());

        try {
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
