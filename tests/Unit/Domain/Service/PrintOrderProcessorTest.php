<?php

declare(strict_types=1);

namespace Tests\Veliu\OrderPrinter\Domain\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Veliu\OrderPrinter\Domain\Address\Address;
use Veliu\OrderPrinter\Domain\Order\Order;
use Veliu\OrderPrinter\Domain\Order\OrderItem;
use Veliu\OrderPrinter\Domain\Order\OrderRepositoryInterface;
use Veliu\OrderPrinter\Domain\Receipt\Receipt;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptGeneratorInterface;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptPositionPrintTypeEnum;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptPrinterInterface;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptSaverInterface;
use Veliu\OrderPrinter\Domain\Service\DefaultPrintOrderProcessor;

#[CoversClass(DefaultPrintOrderProcessor::class)]
class PrintOrderProcessorTest extends TestCase
{
    private OrderRepositoryInterface|MockObject $orderRepository;
    private ReceiptGeneratorInterface|MockObject $receiptGenerator;
    private ReceiptSaverInterface|MockObject $receiptSaver;
    private ReceiptPrinterInterface|MockObject $receiptPrinter;
    private DefaultPrintOrderProcessor $processor;
    private Order $order;
    private Receipt $receipt;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->receiptGenerator = $this->createMock(ReceiptGeneratorInterface::class);
        $this->receiptSaver = $this->createMock(ReceiptSaverInterface::class);
        $this->receiptPrinter = $this->createMock(ReceiptPrinterInterface::class);

        // Create a real Order instance
        $orderItem = new OrderItem(
            productNumber: 'PROD-001',
            label: 'Test Product',
            price: '99.99',
            quantity: 1,
            receiptPositionPrintType: ReceiptPositionPrintTypeEnum::LABEL
        );

        $address = new Address(
            name: 'John Doe',
            street: '123 Test St',
            city: 'Test City',
            phone: '0171 123456',
            additional: null,
        );

        $this->order = new Order(
            identifier: 'ORD-001',
            number: 'ORDER-001',
            customerComment: null,
            totalPrice: '99.99',
            shippingCost: '10.00',
            address: $address,
            items: [$orderItem],
            isNew: true,
            shippingMethodName: 'Lieferung',
            createdAt: new \DateTimeImmutable('2025-07-31')
        );

        // Create a real Receipt instance
        $this->receipt = new Receipt(
            orderNumber: 'ORDER-001',
            content: 'Test receipt content'
        );

        $this->processor = new DefaultPrintOrderProcessor(
            $this->orderRepository,
            $this->receiptGenerator,
            $this->receiptSaver,
            $this->receiptPrinter
        );
    }

    public function testInvokeWithNewOrderAndMarkInProgress(): void
    {
        // Setup
        $filePath = '/path/to/receipt.pdf';

        // Expectations
        $this->receiptGenerator
            ->expects($this->once())
            ->method('fromOrder')
            ->with($this->order)
            ->willReturn($this->receipt);

        $this->receiptSaver
            ->expects($this->once())
            ->method('save')
            ->with($this->receipt)
            ->willReturn($filePath);

        $this->receiptPrinter
            ->expects($this->once())
            ->method('print')
            ->with($this->receipt);

        $this->orderRepository
            ->expects($this->once())
            ->method('markInProgress')
            ->with($this->order);

        // Execute
        $this->processor->__invoke($this->order, true);

        // Additional assertion for the receipt file path
        $this->assertEquals($filePath, $this->receipt->getFilePath());
    }

    public function testInvokeWithoutMarkInProgress(): void
    {
        // Setup
        $filePath = '/path/to/receipt.pdf';

        // Expectations
        $this->receiptGenerator
            ->expects($this->once())
            ->method('fromOrder')
            ->with($this->order)
            ->willReturn($this->receipt);

        $this->receiptSaver
            ->expects($this->once())
            ->method('save')
            ->with($this->receipt)
            ->willReturn($filePath);

        $this->receiptPrinter
            ->expects($this->once())
            ->method('print')
            ->with($this->receipt);

        $this->orderRepository
            ->expects($this->never())
            ->method('markInProgress');

        // Execute
        $this->processor->__invoke($this->order, false);

        // Additional assertion for the receipt file path
        $this->assertEquals($filePath, $this->receipt->getFilePath());
    }

    public function testInvokeWithNonNewOrder(): void
    {
        // Setup
        $filePath = '/path/to/receipt.pdf';

        // Create a non-new order
        $orderItem = new OrderItem(
            productNumber: 'PROD-001',
            label: 'Test Product',
            price: '99.99',
            quantity: 1,
            receiptPositionPrintType: ReceiptPositionPrintTypeEnum::LABEL,
        );

        $address = new Address(
            name: 'John Doe',
            street: '123 Test St',
            city: 'Test City',
            phone: '0171 123456',
            additional: null,
        );

        $nonNewOrder = new Order(
            identifier: 'ORD-001',
            number: 'ORDER-001',
            customerComment: null,
            totalPrice: '99.99',
            shippingCost: '10.00',
            address: $address,
            items: [$orderItem],
            isNew: false,
            shippingMethodName: 'Lieferung',
            createdAt: new \DateTimeImmutable('2025-07-31')

        );

        // Expectations
        $this->receiptGenerator
            ->expects($this->once())
            ->method('fromOrder')
            ->with($nonNewOrder)
            ->willReturn($this->receipt);

        $this->receiptSaver
            ->expects($this->once())
            ->method('save')
            ->with($this->receipt)
            ->willReturn($filePath);

        $this->receiptPrinter
            ->expects($this->once())
            ->method('print')
            ->with($this->receipt);

        $this->orderRepository
            ->expects($this->never())
            ->method('markInProgress');

        // Execute
        $this->processor->__invoke($nonNewOrder, true);

        // Additional assertion for the receipt file path
        $this->assertEquals($filePath, $this->receipt->getFilePath());
    }
}
