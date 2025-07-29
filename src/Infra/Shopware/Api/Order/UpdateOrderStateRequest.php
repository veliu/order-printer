<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\Api\Order;

use Veliu\OrderPrinter\Domain\Api\HasNoAdditionalHeadersTrait;
use Veliu\OrderPrinter\Domain\Api\IsPostTrait;
use Veliu\OrderPrinter\Domain\Api\RequestInterface;
use Veliu\OrderPrinter\Infra\Shopware\OrderStateEnum;

final readonly class UpdateOrderStateRequest implements RequestInterface
{
    use IsPostTrait;
    use HasNoAdditionalHeadersTrait;

    /**
     * @psalm-param non-empty-string $orderId
     */
    public function __construct(
        public string $orderId,
        public OrderStateEnum $state,
    ) {
    }

    #[\Override]
    public function getUri(): string
    {
        return sprintf('/api/_action/order/%s/state/%s', $this->orderId, $this->state->value);
    }

    #[\Override]
    public function getBody(): ?array
    {
        return null;
    }
}
