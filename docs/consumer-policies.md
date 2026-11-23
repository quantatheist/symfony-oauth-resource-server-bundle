# Reusable API consumer authorization policy

`ConfiguredConsumerPolicy` is an implementation of `ConsumerPolicyInterface` for applications with multiple **already validated** OAuth clients.

Example profiles:

```php
$policy = new ConfiguredConsumerPolicy([
    'mobile' => [
        'issuer' => 'https://auth.example/',
        'audience' => 'urn:inventory-api',
        'client_ids' => ['my-mobile-public-client-id'],
        'scopes' => ['inventory:read'],
    ],
]);
```

The policy demands exact issuer, target audience, allowlisted OAuth client, mandatory `client_id == azp` and all configured required scopes. The application chooses read/write requirements per HTTP operation and retains its household/object authorization.

**Critical trust boundary:** `VerifiedAccessToken` carries *already validated* claims, including `client_id`. Do not create it from an unverified JWT decode, user input, or unverified token introspection. Host Symfony Security must first verify the JWT's algorithm, signature, issuer, audience, lifetime and access-token purpose, and must establish a trustworthy `client_id` (e.g. from authenticated, active introspection with consistency checks). This library **does not** implement any of those checks yet. A matching policy is not sufficient evidence of token authenticity or revocation.

- Teatime uses RS256 verification **plus mandatory authenticated introspection**. Preserve this stricter rule and cross-check `iss`, `sub`, `exp`, `aud`, `azp` and effective scopes before constructing the verified token carrier.
- Nailpolishment's Mobile and Home Assistant use separate issuer/audience/consumer profiles. Preserve the Authentik access-token-purpose check and its existing consumer separation; mandatory online revocation remains a deployment-policy decision to be explicitly accepted.
- Registration has no bearer API and should not install this bundle.
- The common bootstrap is exposed by explicit route import at `/.well-known/app-auth/v1`. Legacy `/.well-known/teatime` and `/.well-known/nailpolishment` remain application-controlled compatibility aliases until all Flutter versions migrate.

No new token issuer, client secret, provider registration, OAuth login/refresh, database user, or household rules are introduced. Use native Symfony access-token authentication to bind the authenticated user once a secure handler integration is provided.

**Status:** this PR supplies policy code and tests, NOT a production token handler.
