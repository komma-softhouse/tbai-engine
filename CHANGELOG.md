# Changelog

All notable changes to `komma-softhouse/tbai-engine` are documented here. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and
this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-10-09

### Changed

- The XAdES-EPES signature is built with DOM and OpenSSL only. The XML signing
  library it replaces parsed the certificate with an ASN.1 big-integer class
  that needs `ext-gmp`, which the PHP bundled in NativePHP desktop apps does
  not ship. The signed document keeps the same structure, policies and
  algorithms: enveloped signature, inclusive C14N, RSA-SHA256, signing time,
  `SigningCertificateV2` and the territory's signature policy.
- `QualifyingProperties` now targets the signature by its `Id`, and the
  certificate digest is taken over the certificate's own DER bytes, so the
  signature also passes strict XAdES validators.
- `verifySignature()` checks both reference digests and the signature value
  against the certificate in `KeyInfo`, without the removed library.
- Optional string parameters are declared nullable (`?string`), as PHP 8.4
  requires.

### Removed

- The `lyquidity/xml-signer` dependency, and with it `lyquidity/requester` and
  its `ext-gmp` requirement.

### Added

- `Komma\Tbai\Exception\SignatureException`, thrown when a document cannot be
  signed or its signature does not verify.
