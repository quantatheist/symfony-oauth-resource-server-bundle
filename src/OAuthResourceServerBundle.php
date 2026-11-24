<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class OAuthResourceServerBundle extends Bundle
{
    /**
     * Symfony's default Bundle::getPath() points to src/ for this PSR-4 layout.
     * Shared routing and service configuration live in the package root config/.
     */
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
