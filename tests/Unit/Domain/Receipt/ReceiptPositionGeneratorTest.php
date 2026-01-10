<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Unit\Domain\Receipt;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Veliu\OrderPrinter\Domain\Order\OrderItem;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptPositionGenerator;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptPositionPrintTypeEnum;

#[CoversClass(ReceiptPositionGenerator::class)]
final class ReceiptPositionGeneratorTest extends TestCase
{
    private ReceiptPositionGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->generator = new ReceiptPositionGenerator();
    }

    #[DataProvider('labelTypeProvider')]
    public function testInvokeWithLabelType(string $label, string $expected): void
    {
        $item = new OrderItem(
            productNumber: '26',
            label: $label,
            price: '10.00',
            quantity: 1,
            receiptPositionPrintType: ReceiptPositionPrintTypeEnum::LABEL
        );

        $this->assertSame($expected, ($this->generator)($item));
    }

    public static function labelTypeProvider(): array
    {
        return [
            'simple label' => ['Pizza Mix', 'Pizza Mix'],
            'label with additions' => ['Pizza Mix +Knoblauch', 'Pizza Mix +Knoblauch'],
            'label with parenthesis' => ['Pizza Mix (mild)', 'Pizza Mix (mild)'],
        ];
    }

    #[DataProvider('numberTypeProvider')]
    public function testInvokeWithNumberType(string $label, string $productNumber, string $expected): void
    {
        $item = new OrderItem(
            productNumber: $productNumber,
            label: $label,
            price: '10.00',
            quantity: 1,
            receiptPositionPrintType: ReceiptPositionPrintTypeEnum::NUMBER
        );

        $this->assertSame($expected, ($this->generator)($item));
    }

    public static function numberTypeProvider(): array
    {
        return [
            'simple label' => [
                'label' => 'Pizza Mix',
                'productNumber' => '26',
                'expected' => '26',
            ],
            'label with additions' => [
                'label' => 'Pizza Mix +Knoblauch',
                'productNumber' => '26',
                'expected' => '26 +Knoblauch',
            ],
            'label with parenthesis' => [
                'label' => 'Pizza Mix (mild)',
                'productNumber' => '26',
                'expected' => '26 (mild)',
            ],
            'label with dash' => [
                'label' => 'Pizza Mix - extra cheese',
                'productNumber' => '26',
                'expected' => '26 - extra cheese',
            ],
            'complex label' => [
                'label' => 'Insalata Mista +Extra Dressing (big)',
                'productNumber' => '101',
                'expected' => '101 +Extra Dressing (big)',
            ],
        ];
    }
}
