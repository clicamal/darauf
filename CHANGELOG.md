# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

No unreleased changes yet.

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
