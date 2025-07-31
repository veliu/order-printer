<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Command;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Veliu\OrderPrinter\Domain\Order\OrderRepositoryInterface;
use Veliu\OrderPrinter\Domain\Service\PrintOrderProcessorInterface;

/** @psalm-api */
#[AsMessageHandler]
final readonly class PrintOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private PrintOrderProcessorInterface $printOrderProcessor,
    ) {
    }

    public function __invoke(PrintOrderCommand $command): void
    {
        $order = $this->orderRepository->getByOrderNumber($command->orderNumber);

        ($this->printOrderProcessor)($order, $command->markInProgress);
    }
}
