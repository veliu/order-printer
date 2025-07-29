<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\Api\OAuth;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Veliu\OrderPrinter\Domain\Api\HasNoAdditionalHeadersTrait;
use Veliu\OrderPrinter\Domain\Api\IsPostTrait;
use Veliu\OrderPrinter\Domain\Api\RequestInterface;

final readonly class AccessTokenRequest implements RequestInterface
{
    use IsPostTrait;
    use HasNoAdditionalHeadersTrait;

    public function __construct(
        #[Autowire(env: 'SHOPWARE_CLIENT_ID')]
        public string $clientId,
        #[Autowire(env: 'SHOPWARE_CLIENT_SECRET')]
        public string $clientSecret,
    ) {
    }

    #[\Override]
    public function getUri(): string
    {
        return '/api/oauth/token';
    }

    #[\Override]
    public function getBody(): ?array
    {
        return [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
    }
}
