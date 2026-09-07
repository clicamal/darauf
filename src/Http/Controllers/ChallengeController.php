<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers;

use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Exceptions\VerificationFailedException;
use Illuminate\Contracts\Container\BindingResolutionException;
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
        try {
            $challengeManager = app("darauf.challengeManagers.{$method}");

            $data = $challengeManager->getGenerateChallengeRequestValidator($request->all())->validate();

            $challenge = $challengeManager->generateChallenge($data);

            return response()->json($challenge, 201);
        } catch (DaraufException|BindingResolutionException $exception) {
            if ($exception instanceof BindingResolutionException) {
                return response()->json([
                    'message' => __('darauf::messages.error.verification_method_not_supported'),
                ], 422);
            }

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
        try {
            $challengeManager = app("darauf.challengeManagers.{$method}");

            $data = $challengeManager->getValidateChallengeRequestValidator($request->all())->validate();

            if (! $challengeManager->verifyChallenge($data)) {
                throw new VerificationFailedException;
            }

            return response()->json([
                'message' => __('darauf::messages.success.did_subject_authenticated'),
            ]);
        } catch (DaraufException|BindingResolutionException $exception) {
            if ($exception instanceof BindingResolutionException) {
                return response()->json([
                    'message' => __('darauf::messages.error.verification_method_not_supported'),
                ], 422);
            }

            return response()->json([
                'message' => $exception->getMessage(),
            ], 401);
        }
    }
}
