<?php

namespace Veliu\OrderPrinter\Domain\Service;

use Veliu\OrderPrinter\Domain\Order\Order;

interface PrintOrderProcessorInterface
{
    public function __invoke(Order $order, bool $markInProgress): void;
}
