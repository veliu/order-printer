<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\ResponseObject;

use Psl\Type\TypeInterface;

use function Psl\Type\array_key;
use function Psl\Type\dict;
use function Psl\Type\mixed;
use function Psl\Type\shape;

final readonly class OrderDelivery
{
    public function __construct(
        public CalculatedPrice $shippingCosts,
        public OrderAddress $shippingOrderAddress,
        public Order $order,
    ) {
    }

    public static function fromArray(array $array): self
    {
        $data = self::arrayShape()->coerce($array);

        return new self(
            CalculatedPrice::fromArray($data['shippingCosts']),
            OrderAddress::fromArray($data['shippingOrderAddress']),
            Order::fromArray($data['order']),
        );
    }

    public static function arrayShape(): TypeInterface
    {
        return shape([
            'shippingCosts' => dict(array_key(), mixed()),
            'shippingOrderAddress' => dict(array_key(), mixed()),
            'order' => dict(array_key(), mixed()),
        ]);
    }
}
