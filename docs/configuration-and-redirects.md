# Application API, Authentik and client discovery: configuration boundaries

The host application uses **native Symfony Security** for its API and (optionally) web login. This bundle must not create a second set of web OIDC environment variables or override the identity bridge, native `oidc_login`, or Drenso.

## Three independent configurations

| Concern | Owner | Example |
| --- | --- | --- |
| Browser/web login | Symfony 8.2 `oidc_login` or Drenso | `OIDC_PROVIDER_URI` + `OIDC_CLIENT_ID` + `OIDC_CLIENT_SECRET` (Symfony example); or Drenso `OIDC_WELL_KNOWN_URL` |
| API resource server | Symfony `security.firewalls.api.access_token` and explicit validation policy | Trusted issuer, accepted API audience, permitted OAuth client IDs and token purpose; introspection when mandated |
| Public mobile bootstrap | This bundle / host application's explicit public configuration | Public mobile client ID, provider issuer, optional discovery URL, client scopes, API base URL |

**Never** reuse the confidential Symfony web client ID as the mobile client ID automatically. The public mobile client has no secret; the web client's secret is never returned by discovery.

## Existing apps

- Nailpolishment web login: `OIDC_WELL_KNOWN_URL`, `OIDC_CLIENT_ID`, `OIDC_CLIENT_SECRET` for Drenso; API mobile/HA policies separately use `API_OIDC_MOBILE_*` and `API_OIDC_HOMEASSISTANT_*`. The application bootstrap `/.well-known/nailpolishment` must remain backward compatible.
- Teatime web login: `OIDC_DISCOVERY_URL`, `OIDC_WEB_CLIENT_ID`, `OIDC_WEB_CLIENT_SECRET`, `OIDC_WEB_REDIRECT_URI`. The current API token policy separately defines the accepted API audience/clients. **No forced ENV rename** in either application.
- A new Symfony 8.2 app can use the official `OIDC_PROVIDER_URI`, `OIDC_CLIENT_ID`, `OIDC_CLIENT_SECRET` convention in its `security.yaml`; the resource server doesn't read the values by magic.

### Public bootstrap v1 (host opt-in, not yet an active endpoint)

```json
{
  "version": 1,
  "api": {"base_url": "https://api.example.test/api"},
  "oauth": {"clients": {"mobile": {
    "issuer": "https://auth.example.test/",
    "discovery_url": "https://auth.example.test/application/o/mobile/.well-known/openid-configuration",
    "client_id": "mobile-public-id",
    "authorization_code": true,
    "pkce": "S256",
    "scopes": ["openid", "profile", "offline_access"]
  }}}
}
```

The issuer/discovery URL of the public mobile client is drawn from the **explicit approved mobile profile**. Do not expose the secret or blindly reuse the web provider configuration. A global Authentik issuer may still need a provider-specific discovery URL.

## Redirect URLs in Authentik

- **Web login, Symfony 8.2 native**: register `https://app.example.test/oidc/callback` if the default `oidc_login.check_path` is used. Check `security.route_loader` and public HTTPS origin.
- **Web login, Drenso**: register `https://app.example.test/login_check` for the default `oidc.check_path`, or the explicitly configured absolute callback (e.g. Teatime's `/login/oidc/check`). The Drenso client's `redirect_route` must agree.
- **Flutter public client**: register the exact, app/platform-specific callback configured by the Flutter OIDC SDK. It is NOT either Symfony callback, and must have PKCE S256 and no client secret.
- **API resource server**: no OAuth login redirect; accepts and validates access tokens only.

No callback can be inferred securely from an untrusted incoming `Host` header. Resolve paths against the application's explicitly configured public origin (including any base path / reverse proxy prefix). Use a strict Redirect URI allowlist in Authentik; no wildcards.

## Standards

- OIDC Discovery is fetched **directly from Authentik**, not reimplemented as a token proxy.
- Native Symfony Security `access_token` handles authentication; API-specific trusted issuer/audience/client/token-purpose policy must fail closed.
- Symfony 8.2 native RFC 9728 resource metadata is preferable to reproducing it in a custom controller.
- The app bootstrap is a separate application-specific v1 contract. It publishes **only public client settings** and must not create new trusted issuers dynamically.
- Access tokens go to Symfony APIs; refresh tokens go only to Authentik's token endpoint via the SDK.
- Use a separately reviewed audience model: the API audience, an OIDC client ID and `azp` are not interchangeable by default.

## Documentation

- https://symfony.com/doc/8.2/security/oidc_login.html
- https://symfony.com/doc/8.2/security/access_token.html
- https://github.com/Drenso/symfony-oidc
