# Explicit resource-server validation strategy

Use `jwt_introspection` as the existing application's default until an operator explicitly opts into `jwt`. Unknown values must fail closed. Never catch an online validation failure and retry in JWT-only mode.

Both strategies require native Symfony signature, algorithm, issuer, audience and time validation first. The shared Authentik claim guard then requires an access-token-only uid marker, exact authorized party, subject, scope and integer timestamps. Do not configure custom mappings that add uid to ID tokens. Verify actual provider token formats in staging. JWT-only mode additionally rejects lifetimes exceeding 300 seconds. Configure the mobile provider accordingly. The guard does not validate signatures or perform introspection itself.

JWT-only mode cannot detect grant revocation or upstream group changes before token expiration; verify refresh behavior, upstream disabled-account policy and fresh groups. Local household permissions must be checked on every access. Keep Home Assistant on its existing online mode until explicitly reviewed separately. No additional confidential provider is needed for JWT-only mobile consumers.

Integration sequence: merge and publish bundle, update application dependency locks with Composer, wire explicit per-consumer strategy into both handlers, preserve online validation and scope intersection, then run real signed-token negative tests and device acceptance. Do not enable production API until these steps pass.
