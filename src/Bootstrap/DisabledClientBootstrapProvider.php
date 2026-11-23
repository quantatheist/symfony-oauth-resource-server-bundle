<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Bootstrap;

/**
 * Default provider, intentionally unusable until a host app explicitly opts in.
 * The controller turns this exception into a generic HTTP 503 (no-store).
 */
final readonly class DisabledClientBootstrapProvider implements ClientBootstrapProviderInterface
{
    public function publicDocument(): array
    {
        throw new \LogicException('Public mobile client bootstrap is not configured.');
    }
}
