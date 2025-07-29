<?php

namespace Veliu\OrderPrinter\Domain\Order;

use Veliu\OrderPrinter\Domain\Order\Exception\OrderNotFound;

/**
 * @psalm-api
 */
interface OrderRepositoryInterface
{
    /**
     * @psalm-param non-empty-string $number
     *
     * @throws OrderNotFound
     */
    public function getByOrderNumber(string $number): Order;

    /**
     * @return string[]
     *
     * @psalm-return list<non-empty-string>
     */
    public function findNewNumbers(): array;

    public function markInProgress(Order $order): void;
}
