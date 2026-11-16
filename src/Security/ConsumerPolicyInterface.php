<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Security;

interface ConsumerPolicyInterface
{
    /** Only called after signature, issuer, audience, lifetime and purpose checks. */
    public function accepts(VerifiedAccessToken $token, string $consumerId): bool;
}
