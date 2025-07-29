<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Command;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage(transport: 'async')]
final readonly class PrintOrderCommand
{
    /** @psalm-param non-empty-string $orderNumber */
    public function __construct(
        public string $orderNumber,
        public bool $markInProgress,
    ) {
    }
}
