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

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
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
