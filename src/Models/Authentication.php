<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Models;

use Clicamal\Darauf\Database\Factories\AuthenticationFactory;
use Clicamal\Darauf\Models\Concerns\HasSerializedPayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $authentication_id
 * @property string $did_document_id
 * @property string $serialized
 * @property-read array<string, mixed> $payload
 * @property string|null $type
 * @property string|null $publicKeyMultibase
 * @property array<string, mixed>|null $publicKeyJwk
 */
class Authentication extends Model
{
    /** @use HasFactory<AuthenticationFactory> */
    use HasFactory;

    use HasSerializedPayload;

    protected static function newFactory(): AuthenticationFactory
    {
        return new AuthenticationFactory;
    }

    protected $table = 'darauf_authentication';

    protected $fillable = [
        'authentication_id',
        'did_document_id',
        'serialized',
    ];

    /**
     * @return BelongsTo<DidDocument, $this>
     */
    public function didDocument(): BelongsTo
    {
        return $this->belongsTo(DidDocument::class, 'did_document_id', 'id');
    }
}
