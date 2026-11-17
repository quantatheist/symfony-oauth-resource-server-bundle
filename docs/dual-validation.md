# Optional dual validation: native Symfony Security handlers

`DualValidationAccessTokenHandler` combines **two independently configured**
Symfony `AccessTokenHandlerInterface` services and rejects a token unless both
handlers accept the exact same token and return the same user identifier.

## When to use

Use only when a provider-specific security assessment justifies **both**:

1. Native JWT access-token validation: algorithm allowlist, trusted key source, strict `iss`, API `aud`, timestamps and token-purpose policy.
2. Native OAuth2 introspection: `active`, issuer, API audience, sufficient scope and known client authorization policy.

This is **not** a replacement for full access-token purpose/client authorization checks.
Returning a matching `sub` does NOT prove client ID, `azp`, scopes or an access-token type.
Configure these checks in trusted native handlers or a separately reviewed application security policy.

In Symfony 8.2 use native `access_token.token_handler.oidc` / `oauth2` for simple APIs, where appropriate. The dual wrapper is for cases that mandate both; it does not itself implement crypto, token extraction or HTTP introspection.

Example (assuming your application has explicitly configured two **separate**
trusted handler services):

```yaml
services:
    Quantatheist\OAuthResourceServer\Security\DualValidationAccessTokenHandler:
        arguments:
            $jwtValidator: '@app.access_token.jwt_validator'
            $introspectionValidator: '@app.access_token.introspection_validator'

# config/packages/security.yaml (fragment)
security:
    firewalls:
        api:
            stateless: true
            pattern: ^/api
            access_token:
                token_handler: Quantatheist\OAuthResourceServer\Security\DualValidationAccessTokenHandler
```

**Do not use this example until the two referenced services have been implemented,
configured and validated.** A failed introspection must return 401, never degrade
to JWT-only acceptance. Even successful native validations do not automatically
enforce every application-specific `client_id` or `azp` allowlist.

## Known limitations

- It does not select between multiple Authentik OIDC providers.
- It does not parse or expose introspected `client_id` and `scope` claims to consumer policies.
- It does not provision users or bridge API access to local identity links.
- It is not proof that Authentik never introspects an ID token as active.

All of these must be checked before the bundle is declared production-ready.
