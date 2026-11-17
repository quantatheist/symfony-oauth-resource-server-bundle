<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Bootstrap;

/**
 * No project-specific PHP class required: supply this public configuration via
 * Symfony service arguments referencing existing environment variables.
 */
final readonly class ConfiguredClientBootstrapProvider implements ClientBootstrapProviderInterface
{
    /** @param array<string, mixed> $document */
    public function __construct(private array $document)
    {
    }

    public function publicDocument(): array
    {
        return $this->document;
    }
}
