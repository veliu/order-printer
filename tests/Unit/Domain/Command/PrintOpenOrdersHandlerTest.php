<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Unit\Domain\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Veliu\OrderPrinter\Domain\Command\PrintOpenOrdersCommand;
use Veliu\OrderPrinter\Domain\Command\PrintOpenOrdersHandler;
use Veliu\OrderPrinter\Domain\Command\PrintOrderCommand;
use Veliu\OrderPrinter\Domain\Order\OrderRepositoryInterface;

#[CoversClass(PrintOpenOrdersHandler::class)]
final class PrintOpenOrdersHandlerTest extends TestCase
{
    private OrderRepositoryInterface&MockObject $orderRepository;
    private MessageBusInterface&MockObject $messageBus;
    private PrintOpenOrdersHandler $handler;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->handler = new PrintOpenOrdersHandler(
            $this->orderRepository,
            $this->messageBus
        );
    }

    public function testInvoke(): void
    {
        // Arrange
        $orderNumbers = ['ORDER-001', 'ORDER-002', 'ORDER-003'];
        $markInProgress = true;
        $command = new PrintOpenOrdersCommand($markInProgress);

        $this->orderRepository
            ->expects(self::once())
            ->method('findNewNumbers')
            ->willReturn($orderNumbers);

        $this->messageBus
            ->expects(self::exactly(count($orderNumbers)))
            ->method('dispatch')
            ->willReturnCallback(function (PrintOrderCommand $command) use ($markInProgress) {
                static $index = 0;
                $expectedOrderNumbers = ['ORDER-001', 'ORDER-002', 'ORDER-003'];

                self::assertSame($expectedOrderNumbers[$index], $command->orderNumber);
                self::assertSame($markInProgress, $command->markInProgress);

                ++$index;

                return new Envelope($command);
            });

        // Act
        $this->handler->__invoke($command);
    }
}
