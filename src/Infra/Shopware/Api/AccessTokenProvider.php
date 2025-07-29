<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\Api;

use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Veliu\OrderPrinter\Domain\Api\ClientInterface;
use Veliu\OrderPrinter\Infra\Shopware\Api\OAuth\AccessTokenRequest;
use Veliu\OrderPrinter\Infra\Shopware\Api\OAuth\AccessTokenResponse;

/**
 * @psalm-api
 */
final class AccessTokenProvider implements AccessTokenProviderInterface
{
    private ?AccessTokenInterface $accessToken = null;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly ClockInterface $clock,
        #[Autowire(env: 'SHOPWARE_CLIENT_ID')]
        private readonly string $clientId,
        #[Autowire(env: 'SHOPWARE_CLIENT_SECRET')]
        private readonly string $clientSecret,
    ) {
    }

    #[\Override]
    public function provideAccessToken(): AccessTokenInterface
    {
        if ($this->accessToken && !$this->accessToken->isExpired($this->clock->now())) {
            return $this->accessToken;
        }

        $response = AccessTokenResponse::fromResponse(
            $this->client->request(
                new AccessTokenRequest($this->clientId, $this->clientSecret)
            )
        );

        $expiresAt = $this->clock->now()->modify(sprintf('+%dseconds', $response->expiresIn - 10));

        return $this->accessToken = new AccessToken(
            $response->tokenType,
            $response->accessToken,
            $expiresAt
        );
    }
}
