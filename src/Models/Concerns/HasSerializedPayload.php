<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Decodes the serialized JSON payload stored by Darauf models.
 *
 * @mixin Model
 */
trait HasSerializedPayload
{
    /**
     * The decoded payload stored in the serialized column.
     *
     * @return array<string, mixed>
     */
    public function getPayloadAttribute(): array
    {
        $payload = json_decode($this->serialized ?? '', true);

        return is_array($payload) ? $payload : [];
    }

    public function getTypeAttribute(): ?string
    {
        $type = $this->payload['type'] ?? null;

        return is_string($type) ? $type : null;
    }

    public function getPublicKeyMultibaseAttribute(): ?string
    {
        $key = $this->payload['publicKeyMultibase'] ?? null;

        return is_string($key) ? $key : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPublicKeyJwkAttribute(): ?array
    {
        $jwk = $this->payload['publicKeyJwk'] ?? null;

        return is_array($jwk) ? $jwk : null;
    }
}
