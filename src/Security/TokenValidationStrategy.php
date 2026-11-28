<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Security;

/** Explicit operator choice. Never downgrade on network or validation failure. */
enum TokenValidationStrategy: string
{
    case JWT = 'jwt';
    case JWT_INTROSPECTION = 'jwt_introspection';

    public function requiresIntrospection(): bool
    {
        return $this === self::JWT_INTROSPECTION;
    }

    /**
     * Call ONLY after native signature, issuer, audience and time validation.
     * Authentik's access-token-only uid marker must not be added by custom
     * ID-token scope mappings. Verify this against the deployed provider.
     * The JWT-only mode bounds the stale authorization window to five minutes.
     *
     * @param array<string,mixed> $claims
     */
    public function assertAuthentikAccessToken(array $claims, string $expectedClient, int $now): void
    {
        if ($expectedClient === '' || ($claims['azp'] ?? null) !== $expectedClient
            || !is_string($claims['uid'] ?? null) || trim($claims['uid']) === ''
            || !is_string($claims['sub'] ?? null) || trim($claims['sub']) === ''
            || !is_string($claims['scope'] ?? null)
            || !is_int($claims['iat'] ?? null) || !is_int($claims['exp'] ?? null)
            || $claims['iat'] > $now || $claims['exp'] <= $now
            || $claims['iat'] >= $claims['exp']
            || (array_key_exists('nbf', $claims) && (!is_int($claims['nbf']) || $claims['nbf'] > $now))
            || ($this === self::JWT && $claims['exp'] - $claims['iat'] > 300)
        ) {
            throw new \Symfony\Component\Security\Core\Exception\BadCredentialsException('Invalid credentials.');
        }
    }
}
