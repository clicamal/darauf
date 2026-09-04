<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Models;

use Clicamal\Darauf\Database\Factories\DidDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property string $did_document_id
 * @property string $serialized
 */
#[UseFactory(DidDocumentFactory::class)]
class DidDocument extends Model
{
    /** @use HasFactory<DidDocumentFactory> */
    use HasFactory;

    protected $table = 'darauf_did_documents';

    protected $fillable = [
        'did_document_id',
        'serialized',
    ];

    /**
     * @return HasMany<VerificationMethod, $this>
     */
    public function verificationMethods(): HasMany
    {
        return $this->hasMany(VerificationMethod::class, 'did_document_id', 'id');
    }
}
