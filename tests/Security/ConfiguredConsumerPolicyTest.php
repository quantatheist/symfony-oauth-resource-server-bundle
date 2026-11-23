<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Tests\Security;

use PHPUnit\Framework\TestCase;
use Quantatheist\OAuthResourceServer\Security\ConfiguredConsumerPolicy;
use Quantatheist\OAuthResourceServer\Security\VerifiedAccessToken;

final class ConfiguredConsumerPolicyTest extends TestCase
{
    private function policy(): ConfiguredConsumerPolicy
    {
        return new ConfiguredConsumerPolicy([
            'mobile' => [
                'issuer' => 'https://auth.example/',
                'audience' => 'urn:api',
                'client_ids' => ['mobile-public'],
                'scopes' => ['teatime:read'],
            ],
            'home_assistant' => [
                'issuer' => 'https://auth.example/',
                'audience' => 'urn:api',
                'client_ids' => ['ha-public'],
                'scopes' => ['integration:read'],
            ],
        ]);
    }

    public function testAcceptsOnlyMatchingConsumer(): void
    {
        $token = new VerifiedAccessToken('https://auth.example/', 'subject', ['urn:api'], [
            'client_id' => 'mobile-public', 'azp' => 'mobile-public', 'scope' => 'openid teatime:read',
        ]);
        self::assertTrue($this->policy()->accepts($token, 'mobile'));
        self::assertFalse($this->policy()->accepts($token, 'home_assistant'));
        self::assertFalse($this->policy()->accepts($token, 'unknown'));
    }

    public function testRejectsMissingOrMismatchedAzp(): void
    {
        foreach ([null, 'web-secret-client', 'ha-public'] as $azp) {
            $claims = ['client_id' => 'mobile-public', 'scope' => 'teatime:read'];
            if ($azp !== null) {
                $claims['azp'] = $azp;
            }
            self::assertFalse($this->policy()->accepts(
                new VerifiedAccessToken('https://auth.example/', 'subject', ['urn:api'], $claims), 'mobile'
            ));
        }
    }

    public function testRejectsWrongAudienceIssuerOrScope(): void
    {
        $claims = ['client_id' => 'mobile-public', 'azp' => 'mobile-public', 'scope' => 'openid'];
        foreach ([
            ['https://auth.example/', ['urn:api']],
            ['https://other.example/', ['urn:api']],
            ['https://auth.example/', ['urn:wrong']],
        ] as [$issuer, $audiences]) {
            self::assertFalse($this->policy()->accepts(
                new VerifiedAccessToken($issuer, 'subject', $audiences, $claims), 'mobile'
            ));
        }
    }

    public function testMalformedPolicyFailsDuringConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ConfiguredConsumerPolicy(['mobile' => ['issuer' => 'a', 'audience' => 'b', 'client_ids' => [], 'scopes' => []]]);
    }
}
