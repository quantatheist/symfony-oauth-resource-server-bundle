<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Tests\Bootstrap;

use PHPUnit\Framework\TestCase;
use Quantatheist\OAuthResourceServer\Bootstrap\BootstrapDocumentValidator;
use Quantatheist\OAuthResourceServer\Bootstrap\DisabledClientBootstrapProvider;
use Quantatheist\OAuthResourceServer\Controller\ClientBootstrapController;

final class DisabledClientBootstrapProviderTest extends TestCase
{
    public function testExplicitlyUnconfiguredBootstrapFailsClosed(): void
    {
        $controller = new ClientBootstrapController(
            new DisabledClientBootstrapProvider(),
            new BootstrapDocumentValidator(),
        );

        $response = $controller();
        self::assertSame(503, $response->getStatusCode());
        self::assertSame('{"error":"bootstrap_unavailable"}', $response->getContent());
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }
}
