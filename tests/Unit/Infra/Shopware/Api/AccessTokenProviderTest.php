<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Unit\Infra\Shopware\Api;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;
use Psl\Type\Exception\CoercionException;
use Psr\Clock\ClockInterface;
use Veliu\OrderPrinter\Domain\Api\ApiResponseInterface;
use Veliu\OrderPrinter\Domain\Api\ClientInterface;
use Veliu\OrderPrinter\Infra\Shopware\Api\AccessToken;
use Veliu\OrderPrinter\Infra\Shopware\Api\AccessTokenProvider;
use Veliu\OrderPrinter\Infra\Shopware\Api\OAuth\AccessTokenRequest;

/** @covers \Veliu\OrderPrinter\Infra\Shopware\Api\AccessTokenProvider */
class AccessTokenProviderTest extends TestCase
{
    private const string CLIENT_ID = 'test_client_id';
    private const string CLIENT_SECRET = 'test_client_secret';

    private ClientInterface&MockObject $client;
    private ClockInterface&MockObject $clock;
    private AccessTokenProvider $provider;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->now = new \DateTimeImmutable('2025-01-01 12:00:00');

        $this->provider = new AccessTokenProvider(
            $this->client,
            $this->clock,
            self::CLIENT_ID,
            self::CLIENT_SECRET
        );
    }

    public function testProvideAccessTokenWhenNoTokenExists(): void
    {
        // Arrange
        $expectedTokenType = 'Bearer';
        $expectedAccessToken = 'test_access_token';
        $expiresIn = 3600;

        $this->clock
            ->method('now')
            ->willReturn($this->now);

        $response = $this->createMock(ApiResponseInterface::class);
        $response->method('getBody')
            ->willReturn([
                'token_type' => $expectedTokenType,
                'access_token' => $expectedAccessToken,
                'expires_in' => $expiresIn,
            ]);

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function (AccessTokenRequest $request) {
                return self::CLIENT_ID === $request->clientId
                    && self::CLIENT_SECRET === $request->clientSecret;
            }))
            ->willReturn($response);

        // Act
        $token = $this->provider->provideAccessToken();

        // Assert
        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame("Bearer {$expectedAccessToken}", $token->getAuthorizationHeaderValue());
    }

    public function testProvideAccessTokenReturnsCachedTokenWhenNotExpired(): void
    {
        // Arrange
        $response = $this->createMock(ApiResponseInterface::class);
        $response->method('getBody')
            ->willReturn([
                'token_type' => 'Bearer',
                'access_token' => 'initial_token',
                'expires_in' => 3600,
            ]);

        $this->clock
            ->method('now')
            ->willReturn($this->now);

        $this->client
            ->expects($this->once()) // Should only be called once for initial token
            ->method('request')
            ->willReturn($response);

        // Act
        $firstToken = $this->provider->provideAccessToken();
        $secondToken = $this->provider->provideAccessToken();

        // Assert
        $this->assertSame($firstToken, $secondToken);
    }

    public function testProvideAccessTokenRequestsNewTokenWhenExpired(): void
    {
        // Arrange
        $initialResponse = $this->createMock(ApiResponseInterface::class);
        $initialResponse->method('getBody')
            ->willReturn([
                'token_type' => 'Bearer',
                'access_token' => 'initial_token',
                'expires_in' => 3600,
            ]);

        $newResponse = $this->createMock(ApiResponseInterface::class);
        $newResponse->method('getBody')
            ->willReturn([
                'token_type' => 'Bearer',
                'access_token' => 'new_token',
                'expires_in' => 3600,
            ]);

        $this->clock
            ->method('now')
            ->willReturnOnConsecutiveCalls(
                $this->now,
                $this->now->modify('+2 hours'), // Past expiration
                $this->now->modify('+2 hours')
            );

        $this->client
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($initialResponse, $newResponse);

        // Act
        $firstToken = $this->provider->provideAccessToken();
        $secondToken = $this->provider->provideAccessToken();

        // Assert
        $this->assertNotSame($firstToken, $secondToken);
        $this->assertNotEquals(
            $firstToken->getAuthorizationHeaderValue(),
            $secondToken->getAuthorizationHeaderValue()
        );
    }

    public function testThrowsExceptionWhenDateModificationFails(): void
    {
        // Arrange
        $response = $this->createMock(ApiResponseInterface::class);
        $response->method('getBody')
            ->willReturn([
                'token_type' => 'Bearer',
                'access_token' => 'test_token',
                'expires_in' => 'invalid', // This will cause the sprintf to create an invalid modification string
            ]);

        $this->client
            ->method('request')
            ->willReturn($response);

        $this->clock
            ->method('now')
            ->willReturn($this->now);

        // Assert
        $this->expectException(CoercionException::class);
        $this->expectExceptionMessage('Could not coerce "string" to type "array{\'token_type\': string, \'expires_in\': positive-int, \'access_token\': string}" at path "expires_in".');

        // Act
        $this->provider->provideAccessToken();
    }
}
