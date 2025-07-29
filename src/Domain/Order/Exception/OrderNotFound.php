<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Order\Exception;

final class OrderNotFound extends \RuntimeException
{
    /** @psalm-param non-empty-string $number */
    public function __construct(string $number)
    {
        parent::__construct(sprintf('Order "%s" not found', $number), 404);
    }
}
