<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Models;

use Clicamal\Darauf\Database\Factories\DidDocumentFactory;
use Clicamal\Darauf\Exceptions\InvalidDidException;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(DidDocumentFactory::class)]
class DidDocument extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $primaryKey = 'did';

    protected $keyType = 'string';

    public const string DID_PATTERN = '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/';

    protected $table = 'darauf_did_documents';

    protected $fillable = [
        'did',
    ];

    public function verificationMethods(): HasMany
    {
        return $this->hasMany(VerificationMethod::class, 'did_document_did', 'did');
    }

    public static function generateSha256DidFromUsername(string $username): string
    {
        $usernameHash = hash('sha256', $username);

        $did = (string) 'did:darauf:'.$usernameHash;

        if (! self::validateDid($did)) {
            throw new InvalidDidException;
        }

        return $did;
    }

    /**
     * Validates a DID.
     */
    public static function validateDid(string $did): int|bool
    {
        return preg_match(self::DID_PATTERN, $did);
    }
}
