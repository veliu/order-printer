<?php

namespace Veliu\OrderPrinter\Domain\Receipt;

use Veliu\OrderPrinter\Domain\Order\Order;

/**
 * @psalm-api
 */
interface ReceiptGeneratorInterface
{
    public function fromOrder(Order $order): Receipt;
}
