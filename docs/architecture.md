# OAuth Resource Server architecture

**This PR adds contracts only, not an active HTTP endpoint or token validator.**

## Standards-first responsibilities

- Symfony Security `access_token` owns bearer authentication.
- Symfony's native OIDC JWT handler validates accepted signatures, issuer, audiences and lifetime; on Symfony 8.2, evaluate `enforce_at_jwt_type` for RFC 9068 access-token profiles.
- Symfony 8.2 native RFC 9728 protected-resource metadata should be preferred over custom metadata implementations.
- App-specific client bootstrap is **separate** from RFC 9728 resource metadata and the IdP's OIDC discovery (RFC 8414 / OpenID Connect Discovery).
- A bootstrap document must not authorize new trust anchors, redirect origins or algorithms. Mobile client IDs and scopes are explicitly configured rather than inferred from a confidential Symfony web client.
- Authentication policy must require a proven access-token purpose. Where the issuer's ID/access JWT format overlaps, require an explicit secure token-purpose validation strategy, e.g. mandatory introspection.
- No token issuance, OAuth authorization server, refresh-token endpoint, JWKS cryptography, or user entities in this bundle.
- Multiple issuers and API consumers must have unambiguous allowlists; never trust unverified `kid`, `iss`, `azp`, `jku` or `x5u` to select arbitrary remote keys.
- No JWT-only fallback if mandatory introspection fails.

## Planned PRs

1. Complete versioned JSON Schema and SSRF/host constraints for the bootstrap response.
2. Optional, explicitly enabled well-known bootstrap controller and consistent errors/cache headers.
3. Native Symfony Security integration, with per-consumer policy profiles and RFC 9068/9728 fixtures.
4. Authentik E2E against Nailpolishment/Teatime token profiles.

The Flutter package exchanges authorization codes, stores refresh tokens and refreshes directly against the trusted OIDC provider. The API never receives refresh tokens.
