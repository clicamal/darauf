<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Models;

use Clicamal\Darauf\Database\Factories\VerificationMethodFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $verification_method_id
 * @property string $did_document_id
 * @property string $serialized
 */
#[UseFactory(VerificationMethodFactory::class)]
class VerificationMethod extends Model
{
    /** @use HasFactory<VerificationMethodFactory> */
    use HasFactory;

    protected $table = 'darauf_verification_methods';

    protected $fillable = [
        'verification_method_id',
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
