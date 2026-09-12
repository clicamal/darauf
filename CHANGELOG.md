# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [v0.1.4] - 2026-09-12

### Added

- Pluggable DID resolvers: `DidResolverContract` implementations are registered
  in `config/darauf.php` under `didResolvers`, keyed by DID method, and selected
  per request by `DidResolverDelegator`. A built-in `did:web` resolver fetches
  documents from their canonical URLs.
- `DidDomainModel` and `DidUrlDomainModel` value objects and a static
  `DidValidator` for structured DID document validation.
- An `Authentication` model, migration, factory and `hasMany` relation,
  populated from the `authentication` members of a registered document.
- A shared `HasSerializedPayload` model concern exposing `payload`, `type`,
  `publicKeyMultibase` and `publicKeyJwk` accessors over the `serialized` JSON
  columns, now used across the resolvers, controllers and challenge managers.

### Changed

- The challenge flow now targets a DID URL: `challenge/generate` takes a
  `didUrl`, dereferences it to a verification method or authentication member,
  and returns a single-use `challengeId` and `nonce`; `challenge/verify`
  checks the submitted `signature` over the `nonce` against the dereferenced
  resource's public key on the same routes, without a per-method URL segment.
- `ChallengeManagerContract` is reduced to `verify()`; managers receive the
  dereferenced resource model and no longer own request validation or challenge
  storage.
- `POST /diddocuments` returns a success message; the serialized payload is
  structurally validated on save and its `verificationMethod` and
  `authentication` members are persisted automatically by the model hooks.
- Document registration happens through the `DidDocument` model; the
  `Darauf` core class is downgraded to the container singleton backing the
  facade.
- Deserialization of the `serialized` columns is centralized in the
  `HasSerializedPayload` concern instead of ad-hoc `json_decode` calls.

### Removed

- The `Darauf::createDidDocument()` API, the `DidHelper` helper class, the
  per-error exception subclasses and the local `diddocument/<path>/did.json`
  resolution route for registered `did:web` documents.

## [v0.1.3] - 2026-09-07

### Added

- Configurable challenge managers: `ChallengeManagerContract` implementations
  are registered in a publishable `config/darauf.php` under
  `challengeManagers`, keyed by DID verification method type, and bound to the
  container by the service provider.
- An out-of-the-box `Ed25519ChallengeManager` that resolves the public key from
  the serialized verification method's `publicKeyMultibase` (multibase base64)
  or `publicKeyJwk`, and verifies signatures with `sodium_crypto_sign_verify_detached`.
- `ChallengeNotFoundException`, `ChallengeGenerationFailedException` and
  `InvalidPublicKeyException` domain exceptions with `messages.error.*`
  translations.

### Changed

- The static `Darauf::CHALLENGE_VERIFIERS` map and the RSA verifier framework
  are replaced by container-bound challenge managers; the `ChallengeController`
  resolves a manager by method name
  (`darauf.challengeManagers.{method}`), so new methods need no package changes.
- Challenge managers are registered for both the `Multikey` and
  `Ed25519VerificationKey2020` DID verification method types, reachable on the
  existing `challenge/generate/{method}` and `challenge/verify/{method}` routes.
- A DID document without a matching Ed25519 verification method now throws a
  manager-specific `VerificationMethodNotFoundException` instead of the RSA one.
- The `serialized` columns are no longer cast to arrays on the models, so the
  stored JSON round-trips through `whereJsonContains` queries.

### Removed

- The RSA verifier (`VerificationMethods/RSA`), its `ChallengeVerifierContract`,
  exceptions and the `verification_methods/rsa` translations.

## [v0.1.2] - 2026-09-06

### Added

- `did:web` identifier support. Registering a `did:web` identifier now requires
  the submitted document to match the document published at the DID's canonical
  URL; hosts in private or reserved IP ranges are rejected.
- Resolution of locally registered `did:web` documents via
  `GET /{path}/did.json` (`diddocument/<path>/did.json` or
  `.well-known/did.json`), returning the W3C DID document with `200` and `404`
  for unknown identifiers.
- DID Web URL helpers (`DidHelper::didWebIdToCanonicalUrl` and
  `DidHelper::didWebIdToDaraufUrl`) separating canonical URLs from local
  Darauf URLs.
- Duplicate DID identifiers are reported with a `DuplicatedDidException`
  (`422`) instead of a server error.

### Changed

- The `@context` field in DID documents is validated as a string or an array of
  strings.
- The RSA challenge request validation no longer constrains field lengths
  (`max:100` / `max:512` removed).
- The `challenge/verify` endpoint now responds with `401` when the signature is
  invalid or the challenge is missing or expired.
- The API routes are now served under the `api/darauf/v0.1.2` prefix.

### Fixed

- Request validation failures no longer crash the challenge endpoints with a
  `500`; `ValidationException` is no longer swallowed by a broad catch, so
  validation errors are reported as `422`.
- RSA signature verification now only passes on an exact `1` result from
  `openssl_verify`; an error result (`-1`) was previously treated as a valid
  signature.
- `createDidDocument` runs its writes inside a database transaction, so a
  failure leaves no partial data behind.
- A DID document without an RSA verification method now throws
  `RsaVerificationMethodNotFoundException` instead of
  `ChallengeNotFoundException`.

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

[Unreleased]: https://github.com/clicamal/darauf/compare/v0.1.4...HEAD
[v0.1.4]: https://github.com/clicamal/darauf/compare/v0.1.3...v0.1.4
[v0.1.3]: https://github.com/clicamal/darauf/compare/v0.1.2...v0.1.3
[v0.1.2]: https://github.com/clicamal/darauf/compare/v0.1.1...v0.1.2
[v0.1.1]: https://github.com/clicamal/darauf/compare/v0.1.0...v0.1.1
[v0.1.0]: https://github.com/clicamal/darauf/releases/tag/v0.1.0
