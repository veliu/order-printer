<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\Api;

final readonly class AccessToken implements AccessTokenInterface
{
    public function __construct(
        public string $type,
        public string $token,
        public \DateTimeImmutable $expiresAt,
    ) {
    }

    #[\Override]
    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $now->getTimestamp() > $this->expiresAt->getTimestamp();
    }

    #[\Override]
    public function getAuthorizationHeaderValue(): string
    {
        return sprintf('%s %s', $this->type, $this->token);
    }
}
