<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Tests;

use PHPUnit\Framework\TestCase;
use Quantatheist\OAuthResourceServer\OAuthResourceServerBundle;

final class OAuthResourceServerBundleTest extends TestCase
{
    public function testBundlePathIncludesItsOptionalRoutingConfiguration(): void
    {
        $bundle = new OAuthResourceServerBundle();

        self::assertSame(realpath(__DIR__.'/..'), realpath($bundle->getPath()));
        self::assertFileExists($bundle->getPath().'/config/routes.yaml');
        self::assertFileExists($bundle->getPath().'/config/services.yaml');
    }
}
