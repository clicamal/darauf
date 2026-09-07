<?php

declare(strict_types=1);

namespace Clicamal\Darauf\ChallengeManagers\Ed25519;

use Clicamal\Darauf\ChallengeManagers\ChallengeManagerContract;
use Clicamal\Darauf\ChallengeManagers\Ed25519\Exceptions\VerificationMethodNotFoundException;
use Clicamal\Darauf\Exceptions\ChallengeGenerationFailedException;
use Clicamal\Darauf\Exceptions\ChallengeNotFoundException;
use Clicamal\Darauf\Exceptions\DidDocumentNotFoundException;
use Clicamal\Darauf\Exceptions\InvalidPublicKeyException;
use Clicamal\Darauf\Models\DidDocument;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;

class Ed25519ChallengeManager implements ChallengeManagerContract
{
    public function getGenerateChallengeRequestValidator(array $requestAll): Validator
    {
        return ValidatorFacade::make($requestAll, [
            'didDocumentId' => 'required|string',
        ]);
    }

    public function getValidateChallengeRequestValidator(array $requestAll): Validator
    {
        return ValidatorFacade::make($requestAll, [
            'challengeId' => 'required|string',
            'signature' => 'required|string',
        ]);
    }

    public function generateChallenge(array $data): array
    {
        $didDocument = DidDocument::where('did_document_id', $data['didDocumentId'])->first();

        if (! $didDocument) {
            throw new DidDocumentNotFoundException;
        }

        $verificationMethod = $didDocument->verificationMethods()
            ->where(fn (Builder $query) => $query
                ->whereJsonContains('serialized->type', 'Multikey')
                ->orWhereJsonContains('serialized->type', 'Ed25519VerificationKey2020'))
            ->first();

        if (! $verificationMethod) {
            throw new VerificationMethodNotFoundException;
        }

        $serialized = json_decode($verificationMethod->serialized, true);

        $publicKey = $serialized['publicKeyMultibase'] ?? null;

        if (is_string($publicKey)) {
            $publicKey = $this->multibaseToEd25519($publicKey);
        } else {
            $publicKey = $this->jwkToEd25519($serialized['publicKeyJwk'] ?? []);
        }

        $challengeId = Str::uuid()->toString();
        $challenge = Str::random(32);

        if (! Cache::put("darauf_ed25519_challenge:{$challengeId}", [
            'string' => $challenge,
            'publicKey' => $publicKey,
        ], now()->addMinutes(5))) {
            throw new ChallengeGenerationFailedException;
        }

        return [
            'id' => $challengeId,
            'string' => $challenge,
        ];
    }

    public function verifyChallenge(array $data): bool
    {
        $challenge = Cache::pull("darauf_ed25519_challenge:{$data['challengeId']}");

        if ($challenge === null) {
            throw new ChallengeNotFoundException;
        }

        $signature = base64_decode($data['signature'] ?? '', true);

        if ($signature === false || $signature === '') {
            throw new InvalidPublicKeyException;
        }

        return sodium_crypto_sign_verify_detached($signature, $challenge['string'], $challenge['publicKey']);
    }

    /**
     * Converts a multibase encoded Ed25519 public key to the raw 32-byte key.
     */
    private function multibaseToEd25519(string $multibaseKey): string
    {
        $decoded = base64_decode(strtr(substr($multibaseKey, 1), '-_', '+/'), true);

        if ($decoded === false || strlen($decoded) !== 32) {
            throw new InvalidPublicKeyException;
        }

        return $decoded;
    }

    /**
     * Converts a JSON Web Key to the raw 32-byte Ed25519 public key.
     *
     * @param  array<string, mixed>  $jwk
     */
    private function jwkToEd25519(array $jwk): string
    {
        if (($jwk['kty'] ?? null) !== 'OKP' || ($jwk['crv'] ?? null) !== 'Ed25519') {
            throw new InvalidPublicKeyException;
        }

        $x = $jwk['x'] ?? null;

        if (! is_string($x)) {
            throw new InvalidPublicKeyException;
        }

        $decoded = base64_decode(strtr($x, '-_', '+/'), true);

        if ($decoded === false || strlen($decoded) !== 32) {
            throw new InvalidPublicKeyException;
        }

        return $decoded;
    }
}
