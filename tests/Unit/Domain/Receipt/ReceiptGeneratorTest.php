<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Domain\Receipt;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Veliu\OrderPrinter\Domain\Address\Address;
use Veliu\OrderPrinter\Domain\Order\Order;
use Veliu\OrderPrinter\Domain\Order\OrderItem;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptFormatterInterface;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptGenerator;

/**
 * @covers \Veliu\OrderPrinter\Domain\Receipt\ReceiptGenerator
 */
class ReceiptGeneratorTest extends TestCase
{
    private ReceiptFormatterInterface&MockObject $formatter;
    private ReceiptGenerator $generator;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(ReceiptFormatterInterface::class);
        $this->generator = new ReceiptGenerator($this->formatter);
    }

    public function testFromOrder(): void
    {
        // Arrange
        $order = new Order(
            identifier: '123',
            number: 'ORD-001',
            totalPrice: '99.99',
            shippingCost: '4.99',
            address: new Address(
                name: 'John Doe',
                street: '123 Main St',
                city: 'New York',
                phone: '+1234567890'
            ),
            items: [
                new OrderItem(
                    productNumber: '21',
                    label: 'Pizza Margarita +Extra1 -Extra2',
                    price: '49.99',
                    quantity: 2
                ),
            ],
            isNew: true
        );

        $this->formatter->method('initialize')->willReturnSelf();
        $this->formatter->method('addTitle')->willReturnSelf();
        $this->formatter->method('addDivider')->willReturnSelf();
        $this->formatter->method('addText')->willReturnSelf();
        $this->formatter->method('addTableRow')->willReturnSelf();
        $this->formatter->method('finalize')->willReturnSelf();
        $this->formatter->method('getContents')->willReturn('Receipt content');

        // Act
        $receipt = $this->generator->fromOrder($order);

        // Assert
        $this->assertEquals('ORD-001', $receipt->orderNumber);
        $this->assertEquals('Receipt content', $receipt->content);
    }

    #[DataProvider('extractExtrasProvider')]
    public function testExtractExtras(string $label, array $expectedExtras): void
    {
        $extras = $this->generator->extractExtras($label);
        $this->assertEquals($expectedExtras, $extras);
    }

    public static function extractExtrasProvider(): array
    {
        return [
            'no extras' => [
                'Simple product',
                [],
            ],
            'with extras' => [
                'Product +Extra1 -Extra2',
                ['+Extra1', '-Extra2'],
            ],
            'multiple extras' => [
                'Product +Extra1 +Extra2 -Extra3',
                ['+Extra1', '+Extra2', '-Extra3'],
            ],
            'special characters' => [
                'Product +XL -Käse',
                ['+XL', '-Käse'],
            ],
            'extras with spaces and umlauts' => [
                'Pizza Margaritha +Essig und Öl -Käse +Oliven',
                ['+Essig und Öl', '-Käse', '+Oliven'],
            ],
        ];
    }
}
