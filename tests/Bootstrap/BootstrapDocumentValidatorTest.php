<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Tests\Bootstrap;

use PHPUnit\Framework\TestCase;
use Quantatheist\OAuthResourceServer\Bootstrap\BootstrapDocumentValidator;

final class BootstrapDocumentValidatorTest extends TestCase
{
    /** @return array<string, mixed> */
    private function validDocument(): array
    {
        return [
            'version' => 1,
            'api' => ['base_url' => 'https://api.example.test/api'],
            'oauth' => ['clients' => ['mobile' => [
                'issuer' => 'https://auth.example.test/',
                'client_id' => 'public-client',
                'authorization_code' => true,
                'pkce' => 'S256',
                'scopes' => ['openid', 'offline_access'],
            ]]],
        ];
    }

    public function testAcceptsValidStructure(): void
    {
        (new BootstrapDocumentValidator())->validate($this->validDocument());
        self::assertTrue(true);
    }

    public function testRejectsHttpApiUrl(): void
    {
        $document = $this->validDocument();
        $document['api']['base_url'] = 'http://api.example.test/api';
        $this->expectException(\InvalidArgumentException::class);
        (new BootstrapDocumentValidator())->validate($document);
    }

    public function testRejectsClientWithoutPkce(): void
    {
        $document = $this->validDocument();
        $document['oauth']['clients']['mobile']['pkce'] = 'plain';
        $this->expectException(\InvalidArgumentException::class);
        (new BootstrapDocumentValidator())->validate($document);
    }
}
