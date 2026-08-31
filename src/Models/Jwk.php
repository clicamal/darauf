<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Models;

use Clicamal\Darauf\Database\Factories\JwkFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(JwkFactory::class)]
class Jwk extends Model
{
    use HasFactory;

    protected $table = 'darauf_jwks';

    protected $fillable = [
        'verification_method_id',
        'kty',
        'use',
        'key_ops',
        'kid',
        'e',
        'n',
        'k',
    ];

    public function verificationMethod(): BelongsTo
    {
        return $this->belongsTo(VerificationMethod::class, 'verification_method_id', 'id');
    }
}
