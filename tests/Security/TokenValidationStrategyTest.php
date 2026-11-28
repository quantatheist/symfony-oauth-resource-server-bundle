<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Tests\Security;

use PHPUnit\Framework\TestCase;
use Quantatheist\OAuthResourceServer\Security\TokenValidationStrategy;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class TokenValidationStrategyTest extends TestCase
{
    private function claims(): array
    {
        return ['azp' => 'mobile', 'uid' => 'access-marker', 'sub' => 'user', 'scope' => 'openid read', 'iat' => 1000, 'exp' => 1300];
    }

    public function testExplicitModes(): void
    {
        self::assertFalse(TokenValidationStrategy::from('jwt')->requiresIntrospection());
        self::assertTrue(TokenValidationStrategy::from('jwt_introspection')->requiresIntrospection());
        $this->expectException(\ValueError::class);
        TokenValidationStrategy::from('auto');
    }

    public function testValidShortLivedAccessToken(): void
    {
        TokenValidationStrategy::JWT->assertAuthentikAccessToken($this->claims(), 'mobile', 1100);
        self::assertTrue(true);
    }

    public function testRejectsInvalidPurposeClientAndLifetime(): void
    {
        foreach ([
            ['uid' => null], ['uid' => ''], ['azp' => 'web'], ['sub' => ''],
            ['scope' => []], ['iat' => null], ['iat' => 1101], ['exp' => 1100],
            ['exp' => 1301], ['exp' => '1300'], ['nbf' => 1101], ['nbf' => null],
        ] as $invalid) {
            try {
                TokenValidationStrategy::JWT->assertAuthentikAccessToken(array_replace($this->claims(), $invalid), 'mobile', 1100);
                self::fail('Invalid token accepted.');
            } catch (BadCredentialsException) {
                self::assertTrue(true);
            }
        }
    }

    public function testOnlineModeDoesNotImposeOfflineLifetimeLimit(): void
    {
        TokenValidationStrategy::JWT_INTROSPECTION->assertAuthentikAccessToken(array_replace($this->claims(), ['exp' => 4600]), 'mobile', 1100);
        self::assertTrue(true);
    }
}
