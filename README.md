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
Ed25519 verification methods, and prove control of a key through a challenge /
signature flow — without coupling your subjects to an `Authenticatable` model.

## Features

- Register a W3C DID document with its verification methods, persisted as
  serialized JSON.
- Issue single-use, expiring challenges (5 minute TTL) for a DID document's
  Ed25519 verification method.
- Verify an Ed25519 signature against a challenge to prove key control, at the
  moment of the request (stateless).
- Pluggable challenge manager framework (`ChallengeManagerContract`); each
  manager is registered in the package config and resolved from the Laravel
  container, so you add methods without touching the package controllers.
  Ed25519 is included out of the box.
- `did:web` identifiers: registration validates the submitted document against
  the one published at the DID's canonical URL, and registered documents are
  served locally through a resolution route.
- Ships with migrations, translations and API routes under a versioned prefix.

> **`did:web` identifiers:** registering `did:web:example.com:user:alice`
> requires the submitted document to match the document published at
> `https://example.com/user/alice/did.json` (or `.well-known/did.json` for a
> bare host). Once registered, the document is served locally at
> `GET /api/darauf/v0.1.3/diddocument/user/alice/did.json`, and a
> `did:web:example.com` is served at
> `GET /api/darauf/v0.1.3/.well-known/did.json`. Hosts in private or reserved
> IP ranges are rejected.


## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Customization](#customization)
  - [Building your own DID document workflow](#building-your-own-did-document-workflow)
  - [Storing DID documents programmatically](#storing-did-documents-programmatically)
  - [Adding a custom challenge manager](#adding-a-custom-challenge-manager)
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

### Publishing the Configuration

The package ships with a built-in Ed25519 challenge manager. To expose it or add
your own managers, publish and edit the config:

```bash
php artisan vendor:publish --tag="darauf-config"
```

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
`api/darauf/v0.1.3` and use the `api` middleware group.

### 1. Create a DID document

Submit a W3C DID document. Its `id` becomes the stored DID identifier and its
`verificationMethod` entries are persisted alongside it. Keys are supplied
using the `publicKeyMultibase` representation:

```http
POST /api/darauf/v0.1.3/diddocuments
Content-Type: application/json

{
    "id": "did:darauf:9c144d1a1f2e3b4c5d6e7f8a9b0cde01f2a3b4c5d6e7f8a9b0cde",
    "verificationMethod": [
        {
            "id": "did:darauf:9c144d1a1f2e3b4c5d6e7f8a9b0cde01f2a3b4c5d6e7f8a9b0cde#key-1",
            "controller": "did:darauf:9c144d1a1f2e3b4c5d6e7f8a9b0cde01f2a3b4c5d6e7f8a9b0cde",
            "type": "Ed25519VerificationKey2020",
            "publicKeyMultibase": "u1Bpv7Xu..."
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

For a `did:web` identifier, the submitted document must also match the
document published at the identifier's canonical URL, otherwise the request is
rejected with `422`.

### 2. Resolve a DID Web document

Documents registered under a `did:web` identifier are served locally under the
`diddocument` path prefix:

```http
GET /api/darauf/v0.1.3/diddocument/user/alice/did.json
```

A successful request returns `200` with the W3C DID document:

```json
{
    "@context": ["https://www.w3.org/ns/did/v1"],
    "id": "did:web:example.com:user:alice"
}
```

An unknown identifier returns `404`.

### 2.1 Serve documents at their canonical did:web URL

A [did:web resolver](https://w3c-ccg.github.io/did-method-web/) fetches the
document from the identifier's canonical URL, e.g.
`https://example.com:8443/user/alice/did.json`. To serve documents there, point
your web server to redirect those requests to the package route:

```text
GET https://example.com:8443/user/alice/did.json
    -> https://example.com/api/darauf/v0.1.3/diddocument/user/alice/did.json
```

The same applies to a bare host, which resolves under `.well-known`:

```text
GET https://example.com/.well-known/did.json
    -> https://example.com/api/darauf/v0.1.3/.well-known/did.json
```

### 3. Generate a challenge

Request a single-use, expiring challenge for an existing DID document. The
`{method}` segment in the URL names a challenge manager registered in
`config/darauf.php`:

```http
POST /api/darauf/v0.1.3/challenge/generate/Ed25519VerificationKey2020
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

### 4. Verify a signature

Prove control of the key by signing `string` with the private key and
submitting the base64-encoded signature:

```http
POST /api/darauf/v0.1.3/challenge/verify/Ed25519VerificationKey2020
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

Otherwise a `401` is returned with a descriptive message.

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
            'type' => 'Ed25519VerificationKey2020',
            'publicKeyMultibase' => 'u1Bpv7Xu...',
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
    // -> ['id' => ..., 'controller' => ..., 'type' => 'Ed25519VerificationKey2020', ...]
}

// Add a new verification method to an existing document
VerificationMethod::create([
    'verification_method_id' => 'did:darauf:custom-id#key-2',
    'did_document_id' => $found->id,
    'serialized' => json_encode([
        'id' => 'did:darauf:custom-id#key-2',
        'controller' => 'did:darauf:custom-id',
        'type' => 'Ed25519VerificationKey2020',
        'publicKeyMultibase' => 'u1Bpv7Xu...',
    ]),
]);

// Update a verification method's serialized data
$method->update([
    'serialized' => json_encode([
        'id' => 'did:darauf:custom-id#key-1',
        'controller' => 'did:darauf:custom-id',
        'type' => 'Ed25519VerificationKey2020',
        'publicKeyMultibase' => 'u1Bpv7Xu...new-key...',
    ]),
]);
```

Because the models are ordinary Eloquent models, you can use them in your own
controllers, policies, middleware, or observers like any other model.

### Adding a custom challenge manager

Challenge verification is pluggable. The `ChallengeController` resolves a
challenge manager from the container by name (`darauf.challengeManagers.{method}`)
using the registrations in `config/darauf.php`. To add support for a new
verification method (e.g. ECDSA, secp256k1, or a scheme of your own), implement
the `ChallengeManagerContract` interface and register it in the config:

```php
<?php

namespace App\ChallengeManagers\Ecdsa;

use Clicamal\Darauf\ChallengeManagers\ChallengeManagerContract;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class EcdsaChallengeManager implements ChallengeManagerContract
{
    public function getGenerateChallengeRequestValidator(array $requestAll): Validator
    {
        return ValidatorFacade::make($requestAll, [
            'didDocumentId' => 'required|string',
        ]);
    }

    public function getValidateChallengeRequestValidator(array $requestAll): Validator
    {
        return ValidatorFacade::make($requestAll, [
            'challengeId' => 'required|string',
            'signature' => 'required|string',
        ]);
    }

    public function generateChallenge(array $data): array
    {
        // Load the DID document, resolve its verification method, and
        // store a challenge (e.g. in the cache or in your own table).
        $id = Str::uuid()->toString();
        $string = Str::random(32);

        Cache::put("darauf_ecdsa_challenge:{$id}", [
            'string' => $string,
            'publicKey' => $publicKey,
        ], now()->addMinutes(5));

        return ['id' => $id, 'string' => $string];
    }

    public function verifyChallenge(array $data): bool
    {
        $challenge = Cache::pull("darauf_ecdsa_challenge:{$data['challengeId']}");

        if ($challenge === null) {
            throw new \Clicamal\Darauf\Exceptions\ChallengeNotFoundException;
        }

        // Verify the signature against the document's public key.
        return verify_signature($challenge, base64_decode($data['signature'] ?? '', true));
    }
}
```

Then register it in the published `config/darauf.php` under the name used in
the challenge routes. The key names the DID verification method types the
manager handles; `|`-separated names are all bound to the same manager:

```php
return [
    'challengeManagers' => [
        'Multikey|Ed25519VerificationKey2020' => \Clicamal\Darauf\ChallengeManagers\Ed25519\Ed25519ChallengeManager::class,
        'EcdsaSecp256k1VerificationKey2019' => \App\ChallengeManagers\Ecdsa\EcdsaChallengeManager::class,
    ],
];
```

The manager is now available on the existing routes through the `{method}`
segment. Each manager owns its validation rules, its challenge storage, and its
signature check, so you can mix and match methods without changing the
package's controllers:

```http
POST /api/darauf/v0.1.3/challenge/generate/EcdsaSecp256k1VerificationKey2019
POST /api/darauf/v0.1.3/challenge/verify/EcdsaSecp256k1VerificationKey2019
```

### Custom verification method types

The `type` field inside a serialized verification method is free-form. You can
store any W3C DID verification method type — `Ed25519VerificationKey2020`,
`EcdsaSecp256k1VerificationKey2019`, or a custom type of your own — and pair it
with a corresponding `ChallengeManagerContract` registered in the config.
The package ships with Ed25519 out of the box but does not constrain you to it.

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
│   └── darauf.php                  # Versioned API routes (v0.1.3)
├── src/
│   ├── Console/Commands/           # Artisan commands shipped with the package
│   ├── ChallengeManagers/          # Challenge manager contract + Ed25519 implementation
│   │   ├── ChallengeManagerContract.php
│   │   └── Ed25519/
│   │       ├── Ed25519ChallengeManager.php  # Built-in Ed25519 implementation
│   │       └── Exceptions/                  # Manager-specific exceptions
│   ├── Darauf.php                  # Core facade target; createDidDocument()
│   ├── DaraufServiceProvider.php   # Registers config, routes, translations, publishes
│   ├── Database/Factories/         # Eloquent factories for tests
│   ├── Exceptions/                 # Domain exceptions (DaraufException subclasses)
│   ├── Facades/
│   │   └── Darauf.php              # The public facade
│   ├── Helpers/
│   │   └── DidHelper.php           # DID and did:web generation, resolution & validation helpers
│   ├── Http/Controllers/           # DidDocumentController & ChallengeController
│   └── Models/
│       ├── DidDocument.php         # Represents a stored W3C DID document
│       └── VerificationMethod.php  # Represents a verification method bound to a document
└── tests/                          # Pest + Orchestra Testbench test suite
```

Key responsibilities:

- **`Darauf`** (`src/Darauf.php`) is the primary programmatic entry point. It
  owns the `createDidDocument()` method.
- **Challenge managers** (`src/ChallengeManagers/`) implement
  `ChallengeManagerContract` and are wired into the container by the service
  provider from `config/darauf.php`. The `ChallengeController` resolves a
  manager by name per request, so new methods do not require changing package
  code.
- **Models** (`src/Models/`) map to the two migrated tables. `DidDocument`
  stores the serialized JSON document and `hasMany` verification methods;
  `VerificationMethod` belongs to a `DidDocument`.
- **Controllers** (`src/Http/Controllers/`) are thin HTTP wrappers around the
  `Darauf` core and challenge managers, validating requests and mapping
  exceptions to `422` responses.
- **`ChallengeManagerContract`** is the extension seam for verification
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
