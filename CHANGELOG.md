# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

No unreleased changes yet.

## [v0.1.1] - 2026-09-04

### Added

- Pluggable challenge verifier framework (`ChallengeVerifierContract`) with an
  out-of-the-box RSA implementation, exposed under the new
  `challenge/generate/{method}` and `challenge/verify/{method}` routes.
- DID documents and their verification methods are now persisted as serialized
  JSON (`serialized` text columns), replacing the previous JWK-based storage.

### Changed

- `POST /diddocuments` now accepts a full W3C DID document. Its `id` becomes the
  stored identifier and the response returns that `id` under the `did` key.
- Challenge generation now takes a `didDocumentId` instead of a `username`.
- Challenge / verify routes moved to the `challenge/generate/RSA` and
  `challenge/verify/RSA` endpoints.
- RSA verification methods are identified by type (`RSA`) within the serialized
  verification method and keys are stored using `publicKeyMultibase`.
- Signature verification failures are reported with a `422` response.

### Fixed

- Corrected level 7 static analysis errors across `src`.
- Replaced the Laravel 13-only `#[UseModel]` / `#[UseFactory]` attributes and
  resolved factories through `modelName()` / `newFactory()` so the package works
  on Laravel 12.
- Added a package-style CI workflow that pins Pest to the Laravel-compatible
  major for each Testbench matrix entry.

## [v0.1.0] - 2026-08-29

Initial release of the Darauf package:

### Added

- DID document creation endpoint returns a `did:darauf:<sha256(username)>`
  identifier with an associated RSA verification method.
- RSA challenge generation endpoint issuing single-use challenges with a
  5 minute TTL.
- RSA signature verification endpoint proving key control at request time,
  returning `200` on valid signatures and `401` otherwise.
- `darauf_did_documents` and `darauf_verification_methods` migrations.
- English translations for error and success messages.
- Versioned `api/darauf/v0.1.0` API routes under the `api` middleware group.
- Package service provider and facade.

[Unreleased]: https://github.com/clicamal/darauf/compare/v0.1.1...HEAD
[v0.1.1]: https://github.com/clicamal/darauf/compare/v0.1.0...v0.1.1
[v0.1.0]: https://github.com/clicamal/darauf/releases/tag/v0.1.0
