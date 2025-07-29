<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Command;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage(transport: 'async')]
final readonly class PrintOpenOrdersCommand
{
    public function __construct(
        public bool $markInProgress,
    ) {
    }
}
