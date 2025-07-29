<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Adapter\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Veliu\OrderPrinter\Domain\Command\PrintOpenOrdersCommand as PrintOpenOrdersMessage;
use Veliu\OrderPrinter\Domain\Command\PrintOrderCommand as PrintOrderMessage;

/** @psalm-api */
#[AsCommand('app:print-order', description: 'Prints an shopware order')]
final readonly class PrintOrderCommand
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'The shopware order number')]
        ?string $orderNumber = null,
        #[Option(description: 'Print order with open state')]
        bool $allOpen = false,
        #[Option(description: 'Will mark the order as in progress once printed')]
        bool $markInProgress = true,
    ): int {
        if ($allOpen) {
            $this->messageBus->dispatch(new PrintOpenOrdersMessage($markInProgress));
            $io->success('All open orders scheduled');
        } elseif (null !== $orderNumber) {
            $this->messageBus->dispatch(new PrintOrderMessage($orderNumber, $markInProgress));
            $io->success(sprintf('Order "%s" scheduled', $orderNumber));
        } else {
            $io->error('No order number nor --all-open option provided.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
