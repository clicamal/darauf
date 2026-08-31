<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Models;

use Clicamal\Darauf\Database\Factories\VerificationMethodFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(VerificationMethodFactory::class)]
class VerificationMethod extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'darauf_verification_methods';

    protected $fillable = [
        'id',
        'did_document_did',
        'controller',
        'type',
        'publicKeyMultibase',
    ];

    public function didDocument(): BelongsTo
    {
        return $this->belongsTo(DidDocument::class, 'did_document_did', 'did');
    }

    public function controller(): BelongsTo
    {
        return $this->belongsTo(DidDocument::class, 'controller', 'did');
    }
}
