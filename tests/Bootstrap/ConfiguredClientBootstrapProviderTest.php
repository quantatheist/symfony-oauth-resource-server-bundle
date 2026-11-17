<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Tests\Bootstrap;

use PHPUnit\Framework\TestCase;
use Quantatheist\OAuthResourceServer\Bootstrap\ConfiguredClientBootstrapProvider;

final class ConfiguredClientBootstrapProviderTest extends TestCase
{
    public function testReturnsExplicitApplicationConfiguration(): void
    {
        $document = ['version' => 1, 'api' => ['base_url' => 'https://example.test/api']];
        self::assertSame($document, (new ConfiguredClientBootstrapProvider($document))->publicDocument());
    }
}
