<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Tests\Security;

use PHPUnit\Framework\TestCase;
use Quantatheist\OAuthResourceServer\Security\DualValidationAccessTokenHandler;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class DualValidationAccessTokenHandlerTest extends TestCase
{
    public function testBothIndependentValidatorsMustAgree(): void
    {
        $jwt = $this->createMock(AccessTokenHandlerInterface::class);
        $introspection = $this->createMock(AccessTokenHandlerInterface::class);
        $jwt->expects(self::once())->method('getUserBadgeFrom')->with('token')->willReturn(new UserBadge('sub-1'));
        $introspection->expects(self::once())->method('getUserBadgeFrom')->with('token')->willReturn(new UserBadge('sub-1'));
        self::assertSame('sub-1', (new DualValidationAccessTokenHandler($jwt, $introspection))
            ->getUserBadgeFrom('token')->getUserIdentifier());
    }

    public function testRejectedIntrospectionNeverFallsBackToJwtOnly(): void
    {
        $jwt = $this->createMock(AccessTokenHandlerInterface::class);
        $introspection = $this->createMock(AccessTokenHandlerInterface::class);
        $jwt->method('getUserBadgeFrom')->willReturn(new UserBadge('sub-1'));
        $introspection->method('getUserBadgeFrom')->willThrowException(new BadCredentialsException('inactive'));
        $this->expectException(BadCredentialsException::class);
        (new DualValidationAccessTokenHandler($jwt, $introspection))->getUserBadgeFrom('token');
    }

    public function testDifferentSubjectsAreRejected(): void
    {
        $jwt = $this->createMock(AccessTokenHandlerInterface::class);
        $introspection = $this->createMock(AccessTokenHandlerInterface::class);
        $jwt->method('getUserBadgeFrom')->willReturn(new UserBadge('sub-a'));
        $introspection->method('getUserBadgeFrom')->willReturn(new UserBadge('sub-b'));
        $this->expectException(BadCredentialsException::class);
        (new DualValidationAccessTokenHandler($jwt, $introspection))->getUserBadgeFrom('token');
    }

    public function testEmptyTokensAreRejectedWithoutCallingValidators(): void
    {
        $jwt = $this->createMock(AccessTokenHandlerInterface::class);
        $introspection = $this->createMock(AccessTokenHandlerInterface::class);
        $jwt->expects(self::never())->method('getUserBadgeFrom');
        $introspection->expects(self::never())->method('getUserBadgeFrom');
        $this->expectException(BadCredentialsException::class);
        (new DualValidationAccessTokenHandler($jwt, $introspection))->getUserBadgeFrom('');
    }

    public function testCannotReuseSameValidatorForBothChecks(): void
    {
        $validator = $this->createMock(AccessTokenHandlerInterface::class);
        $this->expectException(\InvalidArgumentException::class);
        new DualValidationAccessTokenHandler($validator, $validator);
    }
}
