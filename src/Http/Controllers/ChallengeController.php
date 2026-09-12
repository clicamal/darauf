<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Http\Controllers;

use Clicamal\Darauf\Did\DidResolverDelegator;
use Clicamal\Darauf\Did\DidUrlDomainModel;
use Clicamal\Darauf\Exceptions\ChallengeManagementException;
use Clicamal\Darauf\Exceptions\DaraufException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChallengeController extends Controller
{
    /**
     * Generates a new challenge for the specified resource.
     */
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'didUrl' => ['required', 'string'],
        ]);

        $didUrlString = $data['didUrl'];

        try {
            $didUrl = new DidUrlDomainModel($didUrlString);

            $resource = app(DidResolverDelegator::class)->dereference($didUrl);

            if ($resource === null) {
                throw new ChallengeManagementException('resource_not_found');
            }

            if (! app()->bound('darauf.challengeManagers.'.$resource->type)) {
                throw new ChallengeManagementException('verification_method_not_supported');
            }

            $challengeId = Str::uuid()->toString();
            $nonce = Str::random(32);

            if (! Cache::put('darauf_challenge:'.$challengeId, [
                'resourceType' => $resource->type,
                'didUrl' => $didUrlString,
                'nonce' => $nonce,
            ], now()->addMinutes(5))) {
                throw new ChallengeManagementException('challenge_generation_failed');
            }

            return response()->json([
                'challengeId' => $challengeId,
                'nonce' => $nonce,
            ], 201);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Exception $exception) {
            return response()->json([
                'message' => __('darauf::messages.error.general'),
            ], 400);
        }
    }

    /**
     * Verifies a challenge for the specified resource.
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challengeId' => ['required', 'string'],
            'signature' => ['required', 'string'],
        ]);

        try {
            $challenge = Cache::pull('darauf_challenge:'.$data['challengeId']);

            if (! is_array($challenge)
                || ! is_string($challenge['resourceType'] ?? null)
                || ! is_string($challenge['didUrl'] ?? null)
                || ! is_string($challenge['nonce'] ?? null)) {
                throw new ChallengeManagementException('challenge_not_found');
            }

            $resource = app(DidResolverDelegator::class)->dereference(
                new DidUrlDomainModel($challenge['didUrl']),
            );

            if ($resource === null) {
                throw new ChallengeManagementException('resource_not_found');
            }

            $challengeManager = app('darauf.challengeManagers.'.$challenge['resourceType']);

            if (! $challengeManager->verify($data['signature'], $challenge['nonce'], $resource)) {
                throw new ChallengeManagementException('challenge_verification_failed');
            }

            return response()->json([
                'message' => __('darauf::messages.success.challenge_verified'),
            ]);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 401);
        } catch (Exception $exception) {
            return response()->json([
                'message' => __('darauf::messages.error.general'),
            ], 401);
        }
    }
}
