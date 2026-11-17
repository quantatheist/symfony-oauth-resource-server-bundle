<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Requires BOTH independent, configured Symfony token validation handlers:
 * - JWT signature + issuer + audience + expiry
 * - OAuth2 token introspection (active status, issuer/audience/token purpose)
 *
 * The two handlers see the exact same token. Never fall back to one handler if
 * the other rejects, times out or is unavailable. No JWT parsing is done here.
 */
final readonly class DualValidationAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private AccessTokenHandlerInterface $jwtValidator,
        private AccessTokenHandlerInterface $introspectionValidator,
    ) {
        if ($jwtValidator === $introspectionValidator) {
            throw new \InvalidArgumentException('JWT and introspection validators must be separate services.');
        }
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        if ('' === $accessToken) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        // Either validator may throw (including provider unavailability).
        // There is deliberately NO catch-and-fallback path.
        $jwtBadge = $this->jwtValidator->getUserBadgeFrom($accessToken);
        $introspectionBadge = $this->introspectionValidator->getUserBadgeFrom($accessToken);

        if (!hash_equals(
            $jwtBadge->getUserIdentifier(),
            $introspectionBadge->getUserIdentifier(),
        )) {
            throw new BadCredentialsException('Token identity checks disagree.');
        }

        return $jwtBadge;
    }
}
