# Mobile OIDC parity contract (Android and iOS)

This contract governs Mobile bootstrap, token acceptance and consumer authorization. The Resource Server must never provision users.

**Scope:** Teatime and Nailpolishment Flutter mobile apps and their Symfony API backends. Desktop Flutter is out of scope. Registration remains Web session-only. This is a target contract and migration gate, **not** evidence that the two implementations already behave identically.

## Mandatory same-order flow

1. App starts from a configured, trusted HTTPS Symfony origin. Fetch versioned public bootstrap metadata. Keep application-specific `/.well-known/teatime` and `/.well-known/nailpolishment` as compatibility endpoints; converge on the shared `/.well-known/app-auth/v1` contract only after both clients verify the exact same schema and migration strategy.
2. Bootstrap contains public API/base URL, exact expected issuer, provider discovery URL, public Mobile client ID and scopes. No confidential Web client ID/secret, no dynamic redirect or arbitrary provider URL from untrusted input. Validate HTTPS, origin policy, document format and known client before login.
3. Independently validate the configured Authentik OIDC discovery. Use the OS system browser for Authorization Code + PKCE S256 (required by server), `state` and `nonce`. Native iOS and Android callback URI registrations stay app-specific. No embedded WebView, implicit grant or Flutter desktop flow.
4. Store refresh tokens in platform secure storage, partitioned per API origin + issuer + client ID. Access/ID tokens remain in memory. Refresh and revocation talk directly to the trusted Authentik endpoint, **never** to the Symfony API. A transient provider/network failure does not erase the refresh token. Ambiguous refresh response is not blindly retried.
5. The client sends **only access tokens** as Bearer tokens to Symfony. Validate signature, pinned issuer, target audience, expiration, token purpose, trusted client/azp and operation-specific scopes; where accepted token profiles cannot distinguish ID vs access token reliably, require authenticated introspection and fail closed. No unverified JWT or bootstrap data can establish identity or roles.
6. Only after validation, resolve a local active user by **exact (`issuer`, `subject`)**. Never link by email, `uid`, username, local user ID or equal subject across different issuers. Target Mobile policy is **existing-only**: no user, identity link, household or membership creation during a Bearer request. Explicit audited linking/onboarding happens outside the API authenticator.
7. GET `/api/me` is the authoritative handshake: authenticated local user ID and a clear mobile consumer context. Household membership and application data remain local and app-specific. A 401/403 or unknown mapping prevents an authenticated application session; **do not discard a valid refresh token solely because `/api/me` returned 401**. A confirmed `invalid_grant` on refresh ends the local OAuth session.
8. Enforce local object/household authorization at every endpoint; Authentik global groups do not grant household membership. Write requests are never automatically replayed after a 401. Logout clears local credentials, attempts token revocation and optionally browser end-session. Do not promise provider-wide logout on network failure.

## Cross-app conformance scenarios (same acceptance criteria)

| Scenario | Expected shared result |
| --- | --- |
| Valid, approved Mobile access token + active mapped user | `/api/me` succeeds and returns the stable app-local user ID |
| Unmapped verified Mobile subject | Authentication fails; **no database writes** and refresh token retained on mobile |
| Disabled mapped user | Authentication fails; no writes |
| Valid ID token, revoked, expired, wrong issuer/audience/client, unapproved group or insufficient scope | Denied; no local-user or household writes |
| Same `sub`, different `iss` | Distinct identity; never automatic link |
| Missing/corrupted bootstrap, changed discovery origin, non-HTTPS production origin | Login blocked; no fallback to arbitrary issuer |
| Transient refresh/Authentik failure | Credentials retained; recoverable error |
| Confirmed OAuth `invalid_grant` | Mobile session removed |
| Final `/api/me` 401 | Application session blocked; do not conclude refresh grant was revoked |
| API 401 on a write operation | No automatic retry of the write |
| Household not joined | Denied regardless of global group role |

Run identical scenario names with app-specific fixtures in both Symfony suites and both Flutter suites. Real Authentik/physical Android/iOS device tests remain mandatory before deployment.

## Known divergence and rollout sequence

- **Teatime:** `AuthentikAccessTokenHandler` currently supports `TEATIME_API_PROVISIONING_ENABLED=1`, which can call `UserProvisioningService::provision()` and create a household. Changing this flag before verifying real Mobile mappings may lock out existing users. Gate strict mode behind a read-only mapping audit, genuine Mobile token verification and staging `/api/me` identity/household checks; only then set `TEATIME_API_PROVISIONING_ENABLED=0`.
- **Nailpolishment:** use the strict `OIDC_IDENTITY_MOBILE_ENABLED` path once its existing issuer/subject links are verified; preserve separate Home Assistant consumer behavior and do not silently enable auto-link.
- The current Resource Server bundle contains **post-validation contracts**, not a production token authenticator. Preserve Teatime mandatory introspection and Nailpolishment's existing signature/consumer checks until replacement passes the same tests. Changing these checks is a separate security migration.
- Keep existing public Flutter config and callback URIs until migrations are explicit; do not force identical app-specific URI strings or domain-specific `/api/me` shapes.
- No third Symfony bundle or shared Doctrine User/Household entity. The Identity Bridge defines common interfaces; each app owns persistence and audited provisioning.

## Release gates

1. Baseline tests in both Symfony apps and Flutter clients; snapshot currently observed differences without weakening failures.
2. Verified identity mapping inventory (read-only), then explicit identity links as an audited administrative action; do not infer links from email.
3. Enable existing-only API policy in staging, then verify both physical platform flows, refresh/rotation/revocation, missing mapping, wrong consumer, failure handling and household isolation.
4. Only after security acceptance, refactor duplicated validation/bootstrap code behind stable bundle contracts, preserving stricter behavior; update lockfiles and CI.
5. Release production separately per app with rollback of configuration only where safe; never roll back schema or identity mappings blindly.
