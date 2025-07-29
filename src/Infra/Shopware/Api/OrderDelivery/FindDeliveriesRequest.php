<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\Api\OrderDelivery;

use Veliu\OrderPrinter\Domain\Api\HasNoAdditionalHeadersTrait;
use Veliu\OrderPrinter\Domain\Api\IsPostTrait;
use Veliu\OrderPrinter\Domain\Api\RequestInterface;
use Veliu\OrderPrinter\Infra\Shopware\OrderStateEnum;

final readonly class FindDeliveriesRequest implements RequestInterface
{
    use IsPostTrait;
    use HasNoAdditionalHeadersTrait;

    /** @psalm-param non-empty-string $orderNumber */
    public function __construct(
        public ?string $orderNumber = null,
        public ?OrderStateEnum $state = null,
    ) {
    }

    #[\Override]
    public function getUri(): string
    {
        return '/api/search/order-delivery';
    }

    #[\Override]
    public function getBody(): ?array
    {
        $body = [
            'filter' => [
                [
                    'type' => 'equals',
                    'field' => 'order.lineItems.type',
                    'value' => 'container',
                ],
            ],
            'associations' => [
                'order' => [
                    'associations' => ['lineItems' => [], 'stateMachineState' => []],
                ],
                'shippingOrderAddress' => [],
            ],
        ];

        if (null !== $this->state) {
            $body['filter'][] = [
                'type' => 'equals',
                'field' => 'order.stateMachineState.technicalName',
                'value' => $this->state->value,
            ];
        }

        if (null !== $this->orderNumber) {
            $body['filter'][] = [
                'type' => 'equals',
                'field' => 'order.orderNumber',
                'value' => $this->orderNumber,
            ];
        }

        return $body;
    }
}
