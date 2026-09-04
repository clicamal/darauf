<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Models;

use Clicamal\Darauf\Database\Factories\VerificationMethodFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(VerificationMethodFactory::class)]
class VerificationMethod extends Model
{
    use HasFactory;

    protected $table = 'darauf_verification_methods';

    protected $fillable = [
        'verification_method_id',
        'did_document_id',
        'serialized',
    ];

    public function didDocument(): BelongsTo
    {
        return $this->belongsTo(DidDocument::class, 'did_document_id', 'id');
    }
}
