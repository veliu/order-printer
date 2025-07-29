<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Service;

use Veliu\OrderPrinter\Domain\Order\Order;
use Veliu\OrderPrinter\Domain\Order\OrderRepositoryInterface;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptGeneratorInterface;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptPrinterInterface;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptSaverInterface;

final readonly class PrintOrderProcessor
{
    /** @psalm-api  */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ReceiptGeneratorInterface $receiptGenerator,
        private ReceiptSaverInterface $receiptSaver,
        private ReceiptPrinterInterface $receiptPrinter,
    ) {
    }

    public function __invoke(Order $order, bool $markInProgress): void
    {
        $receipt = $this->receiptGenerator->fromOrder($order);

        $receipt->setFilePath($this->receiptSaver->save($receipt));

        $this->receiptPrinter->print($receipt);

        if ($markInProgress && $order->isNew) {
            $this->orderRepository->markInProgress($order);
        }
    }
}
