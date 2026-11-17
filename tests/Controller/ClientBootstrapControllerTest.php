<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Quantatheist\OAuthResourceServer\Bootstrap\BootstrapDocumentValidator;
use Quantatheist\OAuthResourceServer\Bootstrap\ClientBootstrapProviderInterface;
use Quantatheist\OAuthResourceServer\Controller\ClientBootstrapController;

final class ClientBootstrapControllerTest extends TestCase
{
    public function testReturnsPublicDocumentWhenValid(): void
    {
        $provider = new class implements ClientBootstrapProviderInterface {
            public function publicDocument(): array
            {
                return [
                    'version' => 1,
                    'api' => ['base_url' => 'https://api.example.test/api'],
                    'oauth' => ['clients' => ['mobile' => [
                        'issuer' => 'https://auth.example.test/',
                        'client_id' => 'mobile-public',
                        'authorization_code' => true,
                        'pkce' => 'S256',
                        'scopes' => ['openid', 'offline_access'],
                    ]]],
                ];
            }
        };
        $response = (new ClientBootstrapController($provider, new BootstrapDocumentValidator()))();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('mobile-public', (string) $response->getContent());
        self::assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
    }

    public function testMisconfiguredDocumentFailsClosedWithoutDetails(): void
    {
        $provider = new class implements ClientBootstrapProviderInterface {
            public function publicDocument(): array
            {
                return ['version' => 1, 'api' => ['base_url' => 'http://unsafe.test']];
            }
        };
        $response = (new ClientBootstrapController($provider, new BootstrapDocumentValidator()))();
        self::assertSame(503, $response->getStatusCode());
        self::assertSame('{"error":"bootstrap_unavailable"}', $response->getContent());
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }
}
