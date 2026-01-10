<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Domain\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Veliu\OrderPrinter\Domain\Address\Address;
use Veliu\OrderPrinter\Domain\Command\PrintOrderCommand;
use Veliu\OrderPrinter\Domain\Command\PrintOrderHandler;
use Veliu\OrderPrinter\Domain\Order\Order;
use Veliu\OrderPrinter\Domain\Order\OrderRepositoryInterface;
use Veliu\OrderPrinter\Domain\Receipt\Receipt;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptGeneratorInterface;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptPrinterInterface;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptSaverInterface;
use Veliu\OrderPrinter\Domain\Service\DefaultPrintOrderProcessor;

#[CoversClass(PrintOrderHandler::class)]
final class PrintOrderHandlerTest extends TestCase
{
    private OrderRepositoryInterface&MockObject $orderRepository;
    private ReceiptGeneratorInterface&MockObject $receiptGenerator;
    private ReceiptSaverInterface&MockObject $receiptSaver;
    private ReceiptPrinterInterface&MockObject $receiptPrinter;
    private PrintOrderHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->receiptGenerator = $this->createMock(ReceiptGeneratorInterface::class);
        $this->receiptSaver = $this->createMock(ReceiptSaverInterface::class);
        $this->receiptPrinter = $this->createMock(ReceiptPrinterInterface::class);

        $printOrderProcessor = new DefaultPrintOrderProcessor(
            $this->orderRepository,
            $this->receiptGenerator,
            $this->receiptSaver,
            $this->receiptPrinter
        );

        $this->handler = new PrintOrderHandler(
            $this->orderRepository,
            $printOrderProcessor
        );
    }

    public function testInvokeProcessesOrderCorrectly(): void
    {
        // Arrange
        $orderNumber = 'ORDER-123';
        $command = new PrintOrderCommand($orderNumber, true);

        $order = new Order(
            'some-identifier',
            $orderNumber,
            null,
            '0.00',
            '0.00',
            new Address('Dwight Schrute', 'Schrute Farm', 'Scranton', '123455656', null),
            [],
            true,
            'Lieferung',
            new \DateTimeImmutable('2025-07-31')
        );

        $receipt = new Receipt('123456', 'Test');

        // Set up expectations
        $this->orderRepository
            ->expects($this->once())
            ->method('getByOrderNumber')
            ->with($orderNumber)
            ->willReturn($order);

        $this->receiptGenerator
            ->expects($this->once())
            ->method('fromOrder')
            ->with($order)
            ->willReturn($receipt);

        $this->receiptSaver
            ->expects($this->once())
            ->method('save')
            ->with($receipt)
            ->willReturn('/path/to/receipt.pdf');

        $this->receiptPrinter
            ->expects($this->once())
            ->method('print')
            ->with($receipt);

        $this->orderRepository
            ->expects($this->once())
            ->method('markInProgress')
            ->with($order);

        // Act
        ($this->handler)($command);
    }
}
