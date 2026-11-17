# App/client bootstrap (opt-in)

**Canonical endpoint: `GET /.well-known/app-auth/v1`**

This is an application-specific bootstrap contract, not OIDC Discovery and not RFC 9728 Protected Resource Metadata.

- `/.well-known/app-auth/v1`: Symfony application, public mobile-client configuration, API URL
- `/.well-known/oauth-protected-resource` (or path-specific RFC 9728 equivalent): Symfony 8.2 native protected-resource metadata, configured in `security.firewalls.api.access_token.resource_metadata`
- `https://AUTHENTIK/.../.well-known/openid-configuration`: standard OIDC provider metadata, fetched **directly** from Authentik by the mobile OIDC SDK

## Enable in a host Symfony project

1. Register the bundle in `config/bundles.php` if Symfony Flex has not done so:
   `Quantatheist\\OAuthResourceServer\\OAuthResourceServerBundle::class => ['all' => true]`
2. Add the **explicitly approved PUBLIC client configuration**, shown below, to `config/services.yaml`; use existing envs, not another bundle-specific env namespace.
3. Import the route in `config/routes/oauth_resource_server.yaml`:

   ```yaml
   oauth_resource_server:
       resource: '@OAuthResourceServerBundle/config/routes.yaml'
   ```

4. Make the route public in `security.access_control` before any catch-all authenticated rule:

   ```yaml
   - { path: '^/\\.well-known/app-auth/v1$', methods: [GET, HEAD], roles: PUBLIC_ACCESS }
   ```

   The route currently declares GET only; Symfony generally serves HEAD for GET routes as well, but check the final router. The public endpoint must not depend on a user session or expose a web-client secret.
5. `bin/console debug:router oauth_resource_server_client_bootstrap` verifies registration. With the route import omitted, no public endpoint is enabled.

```yaml
# config/services.yaml — merge these services into the host app.
services:
    Quantatheist\OAuthResourceServer\Bootstrap\ConfiguredClientBootstrapProvider:
        arguments:
            $document:
                version: 1
                api:
                    base_url: '%env(APP_PUBLIC_API_URL)%'
                oauth:
                    clients:
                        mobile:
                            issuer: '%env(OIDC_MOBILE_ISSUER)%'
                            discovery_url: '%env(OIDC_MOBILE_DISCOVERY_URL)%'
                            client_id: '%env(OIDC_MOBILE_CLIENT_ID)%'
                            authorization_code: true
                            pkce: S256
                            scopes: ['openid', 'profile', 'email', 'offline_access']

    Quantatheist\OAuthResourceServer\Bootstrap\ClientBootstrapProviderInterface:
        alias: Quantatheist\OAuthResourceServer\Bootstrap\ConfiguredClientBootstrapProvider
```

The example mobile env values are application-chosen placeholders, **not new framework requirements**. Nailpolishment may keep `API_OIDC_MOBILE_ISSUER`, `API_OIDC_MOBILE_AUDIENCE` (only when this really equals the public client ID), and `API_OIDC_MOBILE_DISCOVERY_BASE_URI` with a deliberate exact discovery URL mapping. Teatime may use its own `OIDC_*_CLIENT_ID` names. The client bootstrap does not read the confidential web `OIDC_CLIENT_SECRET`.

## Example response

```json
{
  "version": 1,
  "api": {"base_url": "https://app.example.test/api"},
  "oauth": {"clients": {"mobile": {
    "issuer": "https://auth.example.test/",
    "discovery_url": "https://auth.example.test/application/o/mobile/.well-known/openid-configuration",
    "client_id": "public-mobile-client-id",
    "authorization_code": true,
    "pkce": "S256",
    "scopes": ["openid", "profile", "email", "offline_access"]
  }}}
}
```

Only the application-provided, validated document is sent. Successful responses use `Cache-Control: public, max-age=300`; invalid configuration gives a generic HTTP 503 with `Cache-Control: no-store`. No token is needed, and clients must not send refresh/access tokens to bootstrap.

## Trust and rollout

- A server address entered into Flutter is an initial trust decision. Require approved HTTPS origins in production.
- Mobile redirect URIs are app/platform registrations, **not** inferred from a web callback and **not** chosen based on this JSON.
- The host must use a *dedicated public mobile client ID*; even with one issuer, web confidential and public mobile OAuth clients are distinct.
- Do not automatically trust new issuers or APIs from untrusted metadata; existing sessions are bound to exact server/issuer/client context.
- The included `BootstrapDocumentValidator` validates structure and HTTPS/scope syntax, **not** a complete destination/SSRF/DNS threat model. Do not enable arbitrary user-controlled provider metadata without additional trust policy.
- Existing `GET /.well-known/nailpolishment` should remain active and unmodified until compatibility tests allow switching apps.
- This PR does not implement a Flutter SDK or the API token validator.

## Symfony 8.2 native API metadata (separate)

```yaml
# config/packages/security.yaml (fragment)
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            access_token:
                token_handler: App\Security\AccessTokenHandler
                resource_metadata:
                    authorization_servers: ['%env(OIDC_PROVIDER_URI)%']
                    scopes_supported: ['openid', 'profile']
                    resource_name: 'Example API'
```

Do not register `/oidc/callback` as a mobile redirect; it is the native Symfony **web** callback. Drenso uses `/login_check` by default for web login. Symfony APIs themselves have no OAuth login redirect.
