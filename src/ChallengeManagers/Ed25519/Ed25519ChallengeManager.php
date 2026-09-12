<?php

declare(strict_types=1);

namespace Clicamal\Darauf\ChallengeManagers\Ed25519;

use Clicamal\Darauf\ChallengeManagers\ChallengeManagerContract;
use Clicamal\Darauf\Exceptions\ChallengeManagementException;
use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\VerificationMethod;

class Ed25519ChallengeManager implements ChallengeManagerContract
{
    public function verify(string $signature, string $nonce, VerificationMethod|Authentication $resource): bool
    {
        $decodedSignature = base64_decode(strtr($signature, '-_', '+/'), true);

        if (! is_string($decodedSignature) || $decodedSignature === '') {
            return false;
        }

        $publicKey = match (true) {
            isset($resource->publicKeyMultibase) => $this->multibaseToEd25519($resource->publicKeyMultibase),
            isset($resource->publicKeyJwk) => $this->jwkToEd25519($resource->publicKeyJwk),
            default => throw new ChallengeManagementException('invalid_verification_method'),
        };

        return sodium_crypto_sign_verify_detached($decodedSignature, $nonce, $publicKey);
    }

    /**
     * @return non-empty-string
     */
    private function multibaseToEd25519(?string $multibase): string
    {
        if ($multibase === null || $multibase === '') {
            throw new ChallengeManagementException('invalid_multibase_key');
        }

        $decoded = match ($multibase[0]) {
            'z' => $this->base58Decode(substr($multibase, 1)),
            'u' => base64_decode(strtr(substr($multibase, 1), '-_', '+/'), true),
            default => null,
        };

        $key = is_string($decoded) && (strlen($decoded) === 32
            || (strlen($decoded) === 34 && str_starts_with($decoded, "\xed\x01")))
            ? substr($decoded, -32)
            : null;

        return $key ?? throw new ChallengeManagementException('invalid_multibase_key');
    }

    /**
     * @param  array<string, mixed>  $jwk
     * @return non-empty-string
     */
    private function jwkToEd25519(?array $jwk): string
    {
        $key = ($jwk['kty'] ?? null) === 'OKP'
            && ($jwk['crv'] ?? null) === 'Ed25519'
            && is_string($jwk['x'] ?? null)
            ? base64_decode(strtr($jwk['x'], '-_', '+/'), true)
            : null;

        return is_string($key) && strlen($key) === 32
            ? $key
            : throw new ChallengeManagementException('invalid_jwk');
    }

    /**
     * Decodes a base58btc encoded string.
     */
    private function base58Decode(string $base58): string
    {
        if ($base58 === '') {
            throw new ChallengeManagementException('invalid_multibase_key');
        }

        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

        $leadingZeros = 0;

        foreach (str_split($base58) as $char) {
            if ($char !== '1') {
                break;
            }

            $leadingZeros++;
        }

        $digits = [0];

        foreach (str_split($base58) as $char) {
            $carry = strpos($alphabet, $char);

            if ($carry === false) {
                throw new ChallengeManagementException('invalid_multibase_key');
            }

            for ($i = count($digits) - 1; $i >= 0; $i--) {
                $carry += $digits[$i] * 58;
                $digits[$i] = $carry % 256;
                $carry = intdiv($carry, 256);
            }

            while ($carry > 0) {
                array_unshift($digits, $carry % 256);
                $carry = intdiv($carry, 256);
            }
        }

        while (count($digits) > 1 && $digits[0] === 0) {
            array_shift($digits);
        }

        $isZero = count($digits) === 1 && $digits[0] === 0;

        $prefixZeros = $isZero ? max(0, $leadingZeros - 1) : $leadingZeros;

        $result = str_repeat("\x00", $prefixZeros);

        foreach ($digits as $digit) {
            $result .= chr(min(255, max(0, $digit)));
        }

        return $result;
    }
}
