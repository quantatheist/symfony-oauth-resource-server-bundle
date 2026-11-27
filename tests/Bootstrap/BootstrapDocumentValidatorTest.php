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
    public function testRejectsForeignDiscoveryOriginAndUnsafeMetadata(): void
    {
        foreach ([
            ['oauth' => ['clients' => ['mobile' => ['discovery_url' => 'https://other.example.test/openid']]]],
            ['oauth' => ['clients' => ['mobile' => ['client_id' => 'public client with spaces']]]],
            ['oauth' => ['clients' => ['mobile' => ['scopes' => ['offline_access']]]]],
            ['oauth' => ['clients' => ['mobile' => ['scopes' => ['openid', 'openid']]]]],
            ['api' => ['base_url' => 'https://api.example.test/other']],
            ['api' => ['base_url' => 'https://api.example.test/%2e%2e/api']],
        ] as $override) {
            $document = $this->validDocument();
            foreach ($override as $key => $value) {
                if ($key === 'oauth') {
                    $document['oauth']['clients']['mobile'] = array_replace($document['oauth']['clients']['mobile'], $value['clients']['mobile']);
                } else {
                    $document[$key] = array_replace($document[$key], $value);
                }
            }
            try {
                (new BootstrapDocumentValidator())->validate($document);
                self::fail('Unsafe bootstrap metadata was accepted.');
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testValidatesProviderSpecificDiscoveryWithSameOrigin(): void
    {
        $document = $this->validDocument();
        $document['oauth']['clients']['mobile']['issuer'] = 'https://auth.example.test/application/o/mobile/';
        $document['oauth']['clients']['mobile']['discovery_url'] = 'https://auth.example.test/application/o/mobile/.well-known/openid-configuration';
        (new BootstrapDocumentValidator())->validate($document);
        self::assertTrue(true);
    }

}
