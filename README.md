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

- Register a subject and obtain a `did:darauf:*` identifier.
- Store RSA verification methods bound to the DID document.
- Issue single-use, expiring challenges (5 minute TTL).
- Verify an RSA signature against a challenge to prove key possession, at the
  moment of the request (stateless).
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
`api/darauf/v0.1.0` and use the `api` middleware group.

### 1. Create a DID document

Register a subject by providing a `username` and an RSA `publicKey` (PEM):

```http
POST /api/darauf/v0.1.0/diddocuments
Content-Type: application/json

{
    "username": "alice",
    "publicKey": "-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
}
```

A successful request returns `201` and stores:

- `darauf_did_documents.did` = `did:darauf:<sha256(username)>`
- `darauf_verification_methods` — the RSA method bound to that DID.

### 2. Generate a challenge

Request a single-use, expiring challenge for an existing DID:

```http
POST /api/darauf/v0.1.0/verification/rsa/challenge
Content-Type: application/json

{
    "username": "alice"
}
```

A successful request returns `201`:

```json
{
    "challenge": {
        "challengeId": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
        "challengeString": "Q8l2fT... (32 random characters)"
    }
}
```

The challenge expires after 5 minutes and can only be consumed once.

### 3. Verify a signature

Prove control of the key by signing `challengeString` with the private key and
submitting the base64-encoded signature:

```http
POST /api/darauf/v0.1.0/verification/rsa/verify
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
