# Symfony OAuth Resource Server Bundle

Provider-neutral extensions around Symfony Security's **native OAuth2 resource-server support**, plus an optional application bootstrap for Flutter / Home Assistant.

## Status: integration preview, NOT production-certified

Available:
- Opt-in `GET /.well-known/app-auth/v1` Symfony controller (not OIDC or RFC 9728 discovery).
- Explicit public client document via `ConfiguredClientBootstrapProvider` or a host implementation of `ClientBootstrapProviderInterface`.
- Strict bootstrap structural/HTTPS/allowlisted-key validation (unknown fields, including client secrets, rejected).
- Optional `DualValidationAccessTokenHandler` requiring two trusted Symfony access-token validators to accept the exact token and identical subject. It **does not** implement cryptography, introspection, client binding, purpose or scope enforcement itself.
- `ConsumerPolicyInterface` and `VerifiedAccessToken` contracts for future verified client policies.

Not yet implemented: automatically configured native JWT + introspection validator services, multi-provider selection, verified client ID/azp/scope policies, complete route-level functional tests and a real Authentik E2E profile.

**Do not deploy as the sole API auth gate.** Continue using the working and audited application-specific security handlers until the above policies and production integration tests are complete.

## Documentation

- [Opt-in discovery endpoint](docs/app-bootstrap.md)
- [Web/Flutter API environment configuration and redirects](docs/configuration-and-redirects.md)
- [Dual JWT + introspection verification adapter](docs/dual-validation.md)
- [Security architecture](docs/architecture.md)

Symfony 8.2 implements RFC 9728 protected-resource metadata natively. Authentik owns its OIDC metadata, authorization and token endpoints. Mobile apps exchange codes with PKCE and refresh tokens directly at Authentik. This bundle does not issue or refresh tokens.

## Development

`composer install` then `vendor/bin/phpunit`. GitHub Actions runs syntax and unit tests on PRs. Release and host-application integration tests remain required.
