<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Models;

use Clicamal\Darauf\Database\Factories\DidDocumentFactory;
use Clicamal\Darauf\Did\DidValidator;
use Clicamal\Darauf\Exceptions\ValidationException;
use Clicamal\Darauf\Models\Concerns\HasSerializedPayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property string $did_document_id
 * @property string $serialized
 * @property-read array<string, mixed> $payload
 */
class DidDocument extends Model
{
    /** @use HasFactory<DidDocumentFactory> */
    use HasFactory;

    use HasSerializedPayload;

    protected static function newFactory(): DidDocumentFactory
    {
        return new DidDocumentFactory;
    }

    protected $table = 'darauf_did_documents';

    protected $fillable = [
        'did_document_id',
        'serialized',
    ];

    protected static function booted(): void
    {
        static::creating(function (DidDocument $didDocument) {
            $payload = $didDocument->payload;

            if ($payload === [] || ! DidValidator::validateDidDocument($payload)) {
                throw new ValidationException('invalid_did_document');
            }
        });

        static::created(function (DidDocument $didDocument) {
            $payload = $didDocument->payload;

            foreach (['verificationMethod', 'authentication'] as $relation) {
                if (! isset($payload[$relation]) || ! is_array($payload[$relation])) {
                    continue;
                }

                foreach ($payload[$relation] as $item) {
                    if (is_string($item) || ! isset($item['id']) || ! is_string($item['id'])) {
                        continue;
                    }

                    if ($relation === 'verificationMethod') {
                        $didDocument->verificationMethods()->create([
                            'verification_method_id' => $item['id'],
                            'serialized' => json_encode($item),
                        ]);
                    } elseif ($relation === 'authentication') {
                        $didDocument->authentications()->create([
                            'authentication_id' => $item['id'],
                            'serialized' => json_encode($item),
                        ]);
                    }
                }
            }
        });
    }

    /**
     * @return HasMany<VerificationMethod, $this>
     */
    public function verificationMethods(): HasMany
    {
        return $this->hasMany(VerificationMethod::class, 'did_document_id', 'id');
    }

    /**
     * @return HasMany<Authentication, $this>
     */
    public function authentications(): HasMany
    {
        return $this->hasMany(Authentication::class, 'did_document_id', 'id');
    }
}
