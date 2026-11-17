<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Tests\Bootstrap;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quantatheist\OAuthResourceServer\Bootstrap\BootstrapDocumentValidator;

final class BootstrapPublicDataPolicyTest extends TestCase
{
    private static function document(): array
    {
        return [
            'version' => 1,
            'api' => ['base_url' => 'https://service.example.test/api'],
            'oauth' => ['clients' => ['mobile' => [
                'issuer' => 'https://auth.example.test/',
                'discovery_url' => 'https://auth.example.test/application/o/mobile/.well-known/openid-configuration',
                'client_id' => 'public-mobile',
                'authorization_code' => true,
                'pkce' => 'S256',
                'scopes' => ['openid', 'profile', 'offline_access'],
            ]]],
        ];
    }

    public function testAcceptsExpectedPublicContract(): void
    {
        (new BootstrapDocumentValidator())->validate(self::document());
        self::assertTrue(true);
    }

    public function testNeverAllowsSecretToLeakInAdditionalClientField(): void
    {
        $document = self::document();
        $document['oauth']['clients']['mobile']['client_secret'] = 'sensitive';
        $this->expectException(\InvalidArgumentException::class);
        (new BootstrapDocumentValidator())->validate($document);
    }

    public function testRejectsUnexpectedTopLevelField(): void
    {
        $document = self::document();
        $document['internal_configuration'] = 'secret';
        $this->expectException(\InvalidArgumentException::class);
        (new BootstrapDocumentValidator())->validate($document);
    }

    public function testRejectsUnexpectedApiField(): void
    {
        $document = self::document();
        $document['api']['internal_service_url'] = 'https://internal.example.test';
        $this->expectException(\InvalidArgumentException::class);
        (new BootstrapDocumentValidator())->validate($document);
    }

    public function testRejectsNonHttpsProvider(): void
    {
        $document = self::document();
        $document['oauth']['clients']['mobile']['issuer'] = 'http://auth.example.test/';
        $this->expectException(\InvalidArgumentException::class);
        (new BootstrapDocumentValidator())->validate($document);
    }

    public function testRejectsProviderUrlWithCredentials(): void
    {
        $document = self::document();
        $document['oauth']['clients']['mobile']['issuer'] = 'https://admin:password@auth.example.test/';
        $this->expectException(\InvalidArgumentException::class);
        (new BootstrapDocumentValidator())->validate($document);
    }

    public function testRejectsMissingOpenidScope(): void
    {
        $document = self::document();
        $document['oauth']['clients']['mobile']['scopes'] = ['profile'];
        $this->expectException(\InvalidArgumentException::class);
        (new BootstrapDocumentValidator())->validate($document);
    }

    public function testRejectsDuplicateScopes(): void
    {
        $document = self::document();
        $document['oauth']['clients']['mobile']['scopes'] = ['openid', 'openid'];
        $this->expectException(\InvalidArgumentException::class);
        (new BootstrapDocumentValidator())->validate($document);
    }
}
