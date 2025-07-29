<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Order;

final readonly class OrderItem
{
    public function __construct(
        public string $productNumber,
        public string $label,
        public string $price,
        public int $quantity,
    ) {
    }
}
