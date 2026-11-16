<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Bootstrap;

/** The host application supplies only non-sensitive public app/client metadata. */
interface ClientBootstrapProviderInterface
{
    /** @return array<string,mixed> */
    public function publicDocument(): array;
}
