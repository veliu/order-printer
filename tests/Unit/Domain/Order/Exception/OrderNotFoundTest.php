<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Domain\Order\Exception;

use PHPUnit\Framework\TestCase;
use Veliu\OrderPrinter\Domain\Order\Exception\OrderNotFound;

/** @covers \Veliu\OrderPrinter\Domain\Order\Exception\OrderNotFound */
final class OrderNotFoundTest extends TestCase
{
    public function testConstructorSetsCorrectMessageAndCode(): void
    {
        // Arrange
        $orderNumber = 'ORD-123';

        // Act
        $exception = new OrderNotFound($orderNumber);

        // Assert
        $this->assertSame(
            sprintf('Order "%s" not found', $orderNumber),
            $exception->getMessage(),
            'Exception message should match the expected format'
        );
        $this->assertSame(
            404,
            $exception->getCode(),
            'Exception code should be 404'
        );
    }

    public function testExceptionInheritsFromRuntimeException(): void
    {
        $exception = new OrderNotFound('ORD-123');

        $this->assertInstanceOf(
            \RuntimeException::class,
            $exception,
            'OrderNotFound should be a RuntimeException'
        );
    }
}
