# Symfony OAuth Resource Server Bundle

Provider-neutral Symfony API authentication and public application bootstrap contracts.

## Status

**Initial contracts only.** No token authenticator, public discovery endpoint, resource-metadata endpoint or refresh endpoint is registered by this package yet. Do not use the scaffold to claim a production-ready resource server.

See [security architecture](docs/architecture.md).

## Contracts

- `ClientBootstrapProviderInterface` delegates non-sensitive application and mobile-client metadata to the host app.
- `BootstrapDocumentValidator` offers initial **structural** checks for the Nailpolishment-compatible `version: 1` format; it is not yet a complete URL/SSRF/host policy.
- `VerifiedAccessToken` is a post-validation data carrier, not a JWT parser.
- `ConsumerPolicyInterface` expresses extra per-API allowlist decisions after trusted token validation.

Prefer Symfony's native Security access token handler and (Symfony 8.2) resource metadata functionality for RFC 9728. OIDC Discovery and token refresh are performed by the external Authorization Server, not this bundle.

## Integration configuration

See [Web, API and Flutter client configuration](docs/configuration-and-redirects.md) for Authentik values, exact callback/redirect URI registration, and existing application ENV compatibility. No new web OIDC env variables are introduced by this bundle.

## Development

`composer install`, then `vendor/bin/phpunit`. Real IdP security acceptance tests are still required.
