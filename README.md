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
Laravel. It helps you register W3C DID documents, resolve and dereference their
verification methods through pluggable resolvers, and prove control of a key
through a challenge / signature flow — without coupling your subjects to an
`Authenticatable` model.

> **Experimental:** Darauf is in an early, experimental phase. The public API,
> routes, storage format and behavior may change drastically between versions
> without notice.

## Features

- Register a W3C DID document, persisted as serialized JSON and structurally
  validated on save. Its `verificationMethod` and `authentication` members are
  stored alongside it.
- Resolve DIDs and dereference DID URLs to verification method / authentication
  members through pluggable resolvers (`did:web` is included out of the box).
- Issue single-use, expiring challenges (5 minute TTL) bound to a dereferenced
  resource.
- Verify an Ed25519 signature over the challenge nonce to prove key control, at
  the moment of the request (stateless).
- Pluggable challenge manager framework (`ChallengeManagerContract`); each
  manager is registered in the package config and resolved from the Laravel
  container, so you add methods without touching the package controllers.
  Ed25519 is included out of the box.
- Ships with migrations, translations and API routes under a versioned prefix.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Customization](#customization)
  - [Building your own DID document workflow](#building-your-own-did-document-workflow)
  - [Adding a DID resolver](#adding-a-did-resolver)
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

The package ships with a built-in Ed25519 challenge manager and a `did:web`
resolver. To expose them or add your own, publish and edit the config:

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
`api/darauf/v0.1.4` and use the `api` middleware group.

### 1. Create a DID document

Submit a W3C DID document. Its `id` becomes the stored DID identifier and its
`verificationMethod` and `authentication` members are persisted alongside it.
Keys are supplied using the `publicKeyMultibase` or `publicKeyJwk`
representation:

```http
POST /api/darauf/v0.1.4/diddocuments
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
    "message": "DID document registered."
}
```

### 2. Generate a challenge

Request a single-use, expiring challenge for a verification method or
authentication member. The `didUrl` is dereferenced through the resolver
registered for the DID's method (for example `did:web:example.com:user:alice#key-1`):

```http
POST /api/darauf/v0.1.4/challenge/generate
Content-Type: application/json

{
    "didUrl": "did:darauf:9c144d1a1f2e3b4c5d6e7f8a9b0cde01f2a3b4c5d6e7f8a9b0cde#key-1"
}
```

A successful request returns `201`:

```json
{
    "challengeId": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "nonce": "Q8l2fT... (32 random characters)"
}
```

The challenge expires after 5 minutes and can only be consumed once.

### 3. Verify a signature

Prove control of the key by signing `nonce` with the private key and submitting
the base64-encoded signature:

```http
POST /api/darauf/v0.1.4/challenge/verify
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
    "message": "Challenge verified successfully."
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
through the HTTP layer. The core building blocks are the models:

```php
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;

// Register a DID document programmatically. The document is structurally
// validated on save and its verificationMethod / authentication members are
// persisted automatically.
$document = DidDocument::create([
    'did_document_id' => 'did:darauf:custom-id',
    'serialized' => json_encode([
        'id' => 'did:darauf:custom-id',
        'verificationMethod' => [
            [
                'id' => 'did:darauf:custom-id#key-1',
                'controller' => 'did:darauf:custom-id',
                'type' => 'Ed25519VerificationKey2020',
                'publicKeyMultibase' => 'u1Bpv7Xu...',
            ],
        ],
    ]),
]);

// Find an existing document and inspect it
$found = DidDocument::where('did_document_id', 'did:darauf:custom-id')->first();

// Decode the serialized W3C document through the shared model concern
$decoded = $found->payload;

// Query the verification methods bound to it
foreach ($found->verificationMethods as $method) {
    $method->type;                    // -> 'Ed25519VerificationKey2020'
    $method->publicKeyMultibase;      // -> 'u1Bpv7Xu...'
    $method->publicKeyJwk;            // -> ['kty' => 'OKP', ...] or null
}
```

The models map to ordinary Eloquent tables and expose the decoded `serialized`
payload through a shared `HasSerializedPayload` concern (`payload`, `type`,
`publicKeyMultibase` and `publicKeyJwk` accessors). Because they are ordinary
Eloquent models, you can use them in your own controllers, policies,
middleware, or observers like any other model.

### Adding a DID resolver

Challenge generation dereferences a `didUrl` through the resolver registered
for the DID's method. To support a new DID method, implement the
`DidResolverContract` interface and register it in the config:

```php
<?php

namespace App\Did\Resolvers;

use Clicamal\Darauf\Did\DidDomainModel;
use Clicamal\Darauf\Did\DidResolverContract;
use Clicamal\Darauf\Did\DidUrlDomainModel;
use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;

class DaraufResolver implements DidResolverContract
{
    public function resolve(DidDomainModel $did): ?DidDocument
    {
        return DidDocument::where('did_document_id', $did->getFull())->first();
    }

    public function dereference(DidUrlDomainModel $didUrl): VerificationMethod|Authentication|null
    {
        return VerificationMethod::where('verification_method_id', $didUrl->getFull())->first()
            ?? Authentication::where('authentication_id', $didUrl->getFull())->first();
    }
}
```

Then register it under the DID method name it serves:

```php
return [
    'didResolvers' => [
        'web' => \Clicamal\Darauf\Did\Resolvers\DidWebResolver::class,
        'darauf' => \App\Did\Resolvers\DaraufResolver::class,
    ],
];
```

### Adding a custom challenge manager

Challenge verification is pluggable. The `ChallengeController` resolves a
challenge manager from the container by verification method type
(`darauf.challengeManagers.{type}`) using the registrations in
`config/darauf.php`. Each manager receives the dereferenced resource read from
the serialized payload through the model concern:

```php
<?php

namespace App\ChallengeManagers\Ecdsa;

use Clicamal\Darauf\ChallengeManagers\ChallengeManagerContract;
use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\VerificationMethod;

class EcdsaChallengeManager implements ChallengeManagerContract
{
    public function verify(string $signature, string $nonce, VerificationMethod|Authentication $resource): bool
    {
        $publicKey = $resource->publicKeyJwk ?? $resource->publicKeyMultibase;

        if ($publicKey === null) {
            return false;
        }

        // Verify the base64-encoded $signature over the $nonce with the
        // resource's public key.
        return my_verify_signature($publicKey, $nonce, base64_decode($signature, true));
    }
}
```

Then register it in the published `config/darauf.php` under the DID
verification method types it handles; `|`-separated names are all bound to the
same manager:

```php
return [
    'challengeManagers' => [
        'Multikey|Ed25519VerificationKey2020' => \Clicamal\Darauf\ChallengeManagers\Ed25519\Ed25519ChallengeManager::class,
        'EcdsaSecp256k1VerificationKey2019' => \App\ChallengeManagers\Ecdsa\EcdsaChallengeManager::class,
    ],
];
```

The manager is now used by the existing `challenge/generate` and
`challenge/verify` routes whenever a resource of that type is dereferenced.
Each manager owns its signature check, so you can mix and match methods without
changing the package's controllers.

### Custom verification method types

The `type` field inside a serialized verification method / authentication
member is free-form. You can store any W3C DID verification method type —
`Ed25519VerificationKey2020`, `EcdsaSecp256k1VerificationKey2019`, or a custom
type of your own — and pair it with a corresponding `ChallengeManagerContract`
registered in the config. The package ships with Ed25519 out of the box but
does not constrain you to it.

## Code Structure

The package follows a conventional Laravel package layout under `src/`:

```text
darauf/
├── config/
│   └── darauf.php                  # Package configuration (didResolvers + challengeManagers)
├── database/
│   └── migrations/                 # darauf_did_documents, darauf_verification_methods & darauf_authentication
├── lang/
│   └── en/                         # Translations (messages.error.*, messages.success.*)
├── routes/
│   └── darauf.php                  # Versioned API routes (v0.1.4)
├── src/
│   ├── ChallengeManagers/          # Challenge manager contract + Ed25519 implementation
│   │   ├── ChallengeManagerContract.php
│   │   └── Ed25519/
│   │       └── Ed25519ChallengeManager.php   # Built-in Ed25519 implementation
│   ├── Darauf.php                  # Container singleton for the facade alias
│   ├── DaraufServiceProvider.php   # Registers config, routes, translations, publishes
│   ├── Database/Factories/         # Eloquent factories for tests
│   ├── Did/                        # DID / DID URL value objects, validation and resolution
│   │   ├── DidDomainModel.php
│   │   ├── DidUrlDomainModel.php
│   │   ├── DidValidator.php
│   │   ├── DidResolverContract.php
│   │   ├── DidResolverDelegator.php
│   │   └── Resolvers/
│   │       └── DidWebResolver.php  # Built-in did:web resolver
│   ├── Exceptions/                 # Domain exceptions (DaraufException subclasses)
│   ├── Facades/
│   │   └── Darauf.php              # The public facade
│   ├── Http/Controllers/           # DidDocumentController & ChallengeController
│   └── Models/
│       ├── Concerns/
│       │   └── HasSerializedPayload.php   # Decodes the serialized JSON columns
│       ├── DidDocument.php         # Represents a stored W3C DID document
│       ├── VerificationMethod.php  # Represents a verification method bound to a document
│       └── Authentication.php      # Represents an authentication member bound to a document
└── tests/                          # Pest + Orchestra Testbench test suite
```

Key responsibilities:

- **DID layer** (`src/Did/`) parses and validates DIDs and DID URLs
  (`DidDomainModel`, `DidUrlDomainModel`, `DidValidator`) and resolves documents
  per method through pluggable resolvers (`DidResolverDelegator`).
- **Challenge managers** (`src/ChallengeManagers/`) implement
  `ChallengeManagerContract::verify()` and are wired into the container by the
  service provider from `config/darauf.php`. The `ChallengeController` resolves
  a manager by the resource's type per request, so new methods do not require
  changing package code.
- **Models** (`src/Models/`) map to the three migrated tables and share the
  `HasSerializedPayload` concern to decode their `serialized` payload.
  `DidDocument` has many `verificationMethods` and `authentications`.
- **Controllers** (`src/Http/Controllers/`) are thin HTTP wrappers validating
  requests and mapping `DaraufException` subclasses to error responses.

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