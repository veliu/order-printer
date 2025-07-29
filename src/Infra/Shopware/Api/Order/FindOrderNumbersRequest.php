<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\Api\Order;

use Veliu\OrderPrinter\Domain\Api\HasNoAdditionalHeadersTrait;
use Veliu\OrderPrinter\Domain\Api\IsPostTrait;
use Veliu\OrderPrinter\Domain\Api\RequestInterface;
use Veliu\OrderPrinter\Infra\Shopware\OrderStateEnum;

final readonly class FindOrderNumbersRequest implements RequestInterface
{
    use IsPostTrait;
    use HasNoAdditionalHeadersTrait;

    public function __construct(
        public OrderStateEnum $state,
    ) {
    }

    #[\Override]
    public function getUri(): string
    {
        return '/api/search/order';
    }

    #[\Override]
    public function getBody(): ?array
    {
        return [
            'includes' => ['order' => ['orderNumber']],
            'filter' => [
                [
                    'type' => 'equals',
                    'field' => 'stateMachineState.technicalName',
                    'value' => $this->state->value,
                ],
            ],
            'associations' => [
                'stateMachineState' => [],
            ],
        ];
    }
}
