<div align="center">
    <h1>Darauf</h1>
    <p>A simple DID protocol compatible authentication system for Laravel.</p>
</div>

<p align="center">
    <a href="https://packagist.org/packages/clicamal/darauf"><img src="https://img.shields.io/packagist/v/clicamal/darauf.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/clicamal/darauf"><img src="https://img.shields.io/packagist/php-v/clicamal/darauf.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/clicamal/darauf"><img src="https://img.shields.io/packagist/l/clicamal/darauf.svg?style=flat-square" alt="License"></a>
    <a href="https://badge.laravel.cloud/badge/clicamal/darauf?style=flat" alt="Laravel versions"><img src="https://badge.laravel.cloud/badge/clicamal/darauf?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/clicamal/darauf/actions"><img alt="GitHub Workflow Status" src="https://img.shields.io/github/actions/workflow/status/clicamal/darauf/laravel.yml?label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/clicamal/darauf"><img src="https://img.shields.io/packagist/dt/clicamal/darauf.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Darauf is a lightweight, DID protocol compatible authentication layer for
Laravel. It helps you issue Decentralized Identifiers (DIDs), register their
RSA verification methods, and prove control of a key through a challenge /
signature flow — without coupling your subjects to an `Authenticatable` model.

## Features

- Register a W3C DID document with its verification methods, persisted as
  serialized JSON.
- Issue single-use, expiring challenges (5 minute TTL) for a DID document's RSA
  verification method.
- Verify an RSA signature against a challenge to prove key control, at the
  moment of the request (stateless).
- Pluggable challenge verifier framework (`ChallengeVerifierContract`); RSA is
  included out of the box.
- Ships with migrations, translations and API routes under a versioned prefix.

> **Note:** `did:web` is not supported yet. The package treats every DID as
> locally registered — it does **not** resolve a `did:web` identifier by
> fetching its DID document from the DID's URL.


## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Customization](#customization)
  - [Building your own DID document workflow](#building-your-own-did-document-workflow)
  - [Storing DID documents programmatically](#storing-did-documents-programmatically)
  - [Adding a custom challenge verifier](#adding-a-custom-challenge-verifier)
  - [Custom verification method types](#custom-verification-method-types)
- [Code Structure](#code-structure)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

## Requirements

- PHP `^8.3`
- Laravel `^12.0` or `^13.0`

## Installation

You can install the package via Composer:

```bash
composer require clicamal/darauf
```

The package's service provider and facade are discovered automatically.

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="darauf-migrations"
php artisan migrate
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="darauf-lang"
```

## Usage

All endpoints are exposed under the versioned API prefix
`api/darauf/v0.1.1` and use the `api` middleware group.

### 1. Create a DID document

Submit a W3C DID document. Its `id` becomes the stored DID identifier and its
`verificationMethod` entries are persisted alongside it. RSA keys are supplied
using the `publicKeyMultibase` representation:

```http
POST /api/darauf/v0.1.1/diddocuments
Content-Type: application/json

{
    "id": "did:darauf:9c144d1a1f2e3b4c5d6e7f8a9b0cde01f2a3b4c5d6e7f8a9b0cde",
    "verificationMethod": [
        {
            "id": "did:darauf:9c144d1a1f2e3b4c5d6e7f8a9b0cde01f2a3b4c5d6e7f8a9b0cde#key-1",
            "controller": "did:darauf:9c144d1a1f2e3b4c5d6e7f8a9b0cde01f2a3b4c5d6e7f8a9b0cde",
            "type": "RSA",
            "publicKeyMultibase": "z4Mk..."
        }
    ]
}
```

A successful request returns `201`:

```json
{
    "did": "did:darauf:9c144d1a1f2e3b4c5d6e7f8a9b0cde01f2a3b4c5d6e7f8a9b0cde"
}
```

### 2. Generate a challenge

Request a single-use, expiring challenge for an existing DID document:

```http
POST /api/darauf/v0.1.1/challenge/generate/RSA
Content-Type: application/json

{
    "didDocumentId": "did:darauf:9c144d1a1f2e3b4c5d6e7f8a9b0cde01f2a3b4c5d6e7f8a9b0cde"
}
```

A successful request returns `201`:

```json
{
    "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "string": "Q8l2fT... (32 random characters)"
}
```

The challenge expires after 5 minutes and can only be consumed once.

### 3. Verify a signature

Prove control of the key by signing `string` with the private key and
submitting the base64-encoded signature:

```http
POST /api/darauf/v0.1.1/challenge/verify/RSA
Content-Type: application/json

{
    "challengeId": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "signature": "base64-encoded-signature"
}
```

If the signature is cryptographically valid, the request returns `200` and the
subject is considered authenticated for that request:

```json
{
    "message": "DID subject authenticated."
}
```

Otherwise a `422` is returned with a descriptive message.

## Customization

The HTTP endpoints cover a straightforward flow, but you are free to extend the
package to fit your domain. Below are the main extension points.

### Building your own DID document workflow

The exposed routes are thin wrappers. If you need a custom registration,
update, or delete workflow (e.g. authenticated by your own user model, an
admin panel, a queue job, or a one-time import script), you do not have to go
through the HTTP layer. The core building blocks are the models and the
`Darauf` facade:

```php
use Clicamal\Darauf\Darauf;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;

// Register a DID document programmatically
$document = Darauf::createDidDocument([
    'id' => 'did:darauf:custom-id',
    'verificationMethod' => [
        [
            'id' => 'did:darauf:custom-id#key-1',
            'controller' => 'did:darauf:custom-id',
            'type' => 'RSA',
            'publicKeyMultibase' => 'z4Mk...',
        ],
    ],
]);

// Find an existing document and inspect it
$found = DidDocument::where('did_document_id', 'did:darauf:custom-id')->first();

// Decode the serialized W3C document
$decoded = json_decode($found->serialized, true);

// Query the verification methods bound to it
foreach ($found->verificationMethods as $method) {
    $methodData = json_decode($method->serialized, true);
    // -> ['id' => ..., 'controller' => ..., 'type' => 'RSA', ...]
}

// Add a new verification method to an existing document
VerificationMethod::create([
    'verification_method_id' => 'did:darauf:custom-id#key-2',
    'did_document_id' => $found->id,
    'serialized' => json_encode([
        'id' => 'did:darauf:custom-id#key-2',
        'controller' => 'did:darauf:custom-id',
        'type' => 'Ed25519',
        'publicKeyMultibase' => 'z6Mk...',
    ]),
]);

// Update a verification method's serialized data
$method->update([
    'serialized' => json_encode([
        'id' => 'did:darauf:custom-id#key-1',
        'controller' => 'did:darauf:custom-id',
        'type' => 'RSA',
        'publicKeyMultibase' => 'z4Mk...new-key...',
    ]),
]);
```

Because the models are ordinary Eloquent models, you can use them in your own
controllers, policies, middleware, or observers like any other model.

### Adding a custom challenge verifier

Challenge verification is pluggable. To add support for a new verification
method (e.g. Ed25519, secp256k1, or a scheme of your own), implement the
`ChallengeVerifierContract` interface and register it under a name:

```php
<?php

namespace App\VerificationMethods\Ed25519;

use Clicamal\Darauf\VerificationMethods\ChallengeVerifierContract;

class Ed25519 implements ChallengeVerifierContract
{
    public static function validateGenerateChallengeRequest(array $requestAll): array
    {
        return Validator::make($requestAll, [
            'didDocumentId' => 'required|string|max:100',
        ])->validate();
    }

    public static function validateVerifyChallengeRequest(array $requestAll): array
    {
        return Validator::make($requestAll, [
            'challengeId' => 'required|string|max:100',
            'signature' => 'required|string|max:512',
        ])->validate();
    }

    public static function generateChallenge(array $data): array
    {
        // Load the DID document, resolve its verification method, and
        // store a challenge (e.g. in the cache or in your own table).
        $id = Str::uuid()->toString();
        $string = Str::random(32);

        Cache::put("darauf_ed25519_challenge:{$id}", $string, now()->addMinutes(5));

        return ['id' => $id, 'string' => $string];
    }

    public static function verifyChallenge(array $data): bool
    {
        $challenge = Cache::pull("darauf_ed25519_challenge:{$data['challengeId']}");

        if ($challenge === null) {
            throw new ChallengeNotFoundException;
        }

        // Verify the signature against the document's public key.
        return verify_signature($challenge, base64_decode($data['signature']));
    }
}
```

Then register it. The package's `ChallengeController` resolves the verifier by
name from the built-in `Darauf::CHALLENGE_VERIFIERS` map. To add a verifier
without touching the dependency, extend the controller and re-implement the two
actions so they resolve from your own map:

```php
<?php

namespace App\Http\Controllers;

use Clicamal\Darauf\Darauf;
use Clicamal\Darauf\Exceptions\DaraufException;
use Clicamal\Darauf\Exceptions\VerificationFailedException;
use Clicamal\Darauf\Exceptions\VerificationMethodNotSupportedException;
use Clicamal\Darauf\VerificationMethods\ChallengeVerifierContract;
use App\VerificationMethods\Ed25519\Ed25519;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeController
{
    /**
     * @var array<string, class-string<ChallengeVerifierContract>>
     */
    protected const CHALLENGE_VERIFIERS = [
        'RSA' => Darauf::CHALLENGE_VERIFIERS['RSA'],
        'Ed25519' => Ed25519::class,
    ];

    public function generateChallenge(Request $request, string $method): JsonResponse
    {
        $verificationMethod = static::CHALLENGE_VERIFIERS[$method] ?? null;

        try {
            if ($verificationMethod === null) {
                throw new VerificationMethodNotSupportedException;
            }

            $data = $verificationMethod::validateGenerateChallengeRequest($request->all());
            $challenge = $verificationMethod::generateChallenge($data);

            return response()->json($challenge, 201);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function verifyChallenge(Request $request, string $method): JsonResponse
    {
        $verificationMethod = static::CHALLENGE_VERIFIERS[$method] ?? null;

        try {
            if ($verificationMethod === null) {
                throw new VerificationMethodNotSupportedException;
            }

            $data = $verificationMethod::validateVerifyChallengeRequest($request->all());

            if (! $verificationMethod::verifyChallenge($data)) {
                throw new VerificationFailedException;
            }

            return response()->json([
                'message' => __('darauf::messages.success.did_subject_authenticated'),
            ]);
        } catch (DaraufException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
```

Then point the `challenge/generate/{method}` and `challenge/verify/{method}`
routes at your controller (register your own route in `routes/api.php` for the
methods you want to expose) so `Ed25519` is reachable:

```http
POST /api/darauf/v0.1.1/challenge/generate/Ed25519
POST /api/darauf/v0.1.1/challenge/verify/Ed25519
```

Once registered, your custom method is exposed on the existing routes:

```http
POST /api/darauf/v0.1.1/challenge/generate/Ed25519
POST /api/darauf/v0.1.1/challenge/verify/Ed25519
```

Each verifier owns its validation rules, its challenge storage, and its
signature check, so you can mix and match methods without changing the
package's controllers.

### Custom verification method types

The `type` field inside a serialized verification method is free-form. You can
store any W3C DID verification method type — `Ed25519VerificationKey2020`,
`EcdsaSecp256k1VerificationKey2019`, or a custom type of your own — and pair it
with a corresponding `ChallengeVerifierContract` registered under that name.
The package ships with `RSA` out of the box but does not constrain you to it.

## Code Structure

The package follows a conventional Laravel package layout under `src/`:

```text
darauf/
├── config/
│   └── darauf.php                  # Package configuration (merged on register)
├── database/
│   └── migrations/                 # darauf_did_documents & darauf_verification_methods
├── lang/
│   └── en/                         # Translations (messages, verification method strings)
├── routes/
│   └── darauf.php                  # Versioned API routes (v0.1.1)
├── src/
│   ├── Console/Commands/           # Artisan commands shipped with the package
│   ├── Darauf.php                  # Core facade target; createDidDocument() + verifier map
│   ├── DaraufServiceProvider.php   # Registers config, routes, translations, publishes
│   ├── Database/Factories/         # Eloquent factories for tests
│   ├── Exceptions/                 # Domain exceptions (DaraufException subclasses)
│   ├── Facades/
│   │   └── Darauf.php              # The public facade
│   ├── Helpers/
│   │   └── DidHelper.php           # DID generation & validation helpers
│   ├── Http/Controllers/           # DidDocumentController & ChallengeController
│   ├── Models/
│   │   ├── DidDocument.php         # Represents a stored W3C DID document
│   │   └── VerificationMethod.php  # Represents a verification method bound to a document
│   └── VerificationMethods/
│       ├── ChallengeVerifierContract.php  # Contract for challenge verification
│       └── RSA/                    # Built-in RSA implementation + its exceptions
└── tests/                          # Pest + Orchestra Testbench test suite
```

Key responsibilities:

- **`Darauf`** (`src/Darauf.php`) is the primary programmatic entry point. It
  owns the `createDidDocument()` method and the `CHALLENGE_VERIFIERS` map that
  names the available verification methods.
- **Models** (`src/Models/`) map to the two migrated tables. `DidDocument`
  stores the serialized JSON document and `hasMany` verification methods;
  `VerificationMethod` belongs to a `DidDocument`.
- **Controllers** (`src/Http/Controllers/`) are thin HTTP wrappers around the
  `Darauf` core, validating requests and mapping exceptions to `422`
  responses.
- **`ChallengeVerifierContract`** is the extension seam for verification
  logic; each implementation owns validation, challenge storage, and signature
  verification.

## Testing

```bash
composer test
```

The package uses [Pest](https://pestphp.com) with
[Orchestra Testbench](https://github.com/orchestral/testbench) for isolation.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed
recently.

## Credits

- [clicamal](https://github.com/clicamal)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more
information.
