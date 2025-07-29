<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Command;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Veliu\OrderPrinter\Domain\Order\OrderRepositoryInterface;

/**
 * @psalm-api
 */
#[AsMessageHandler]
final readonly class PrintOpenOrdersHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(PrintOpenOrdersCommand $command): void
    {
        $orders = $this->orderRepository->findNewNumbers();

        foreach ($orders as $orderNumber) {
            $this->messageBus->dispatch(new PrintOrderCommand($orderNumber, $command->markInProgress));
        }
    }
}
