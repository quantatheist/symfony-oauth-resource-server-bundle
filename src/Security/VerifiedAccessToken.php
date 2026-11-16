<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Security;

/**
 * Data carrier ONLY: the caller must have already used a trusted validator
 * (Symfony native handler / configured introspection) and token purpose policy.
 */
final readonly class VerifiedAccessToken
{
    /** @param list<string> $audiences @param array<string,mixed> $claims */
    public function __construct(
        public string $issuer,
        public string $subject,
        public array $audiences,
        public array $claims = [],
    ) {
        if ('' === trim($issuer) || '' === trim($subject) || [] === $audiences) {
            throw new \InvalidArgumentException('Incomplete verified access token.');
        }
        foreach ($audiences as $audience) {
            if (!is_string($audience) || '' === trim($audience)) {
                throw new \InvalidArgumentException('Invalid audience.');
            }
        }
    }
}
