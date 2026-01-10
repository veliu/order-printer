<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Adapter\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Veliu\OrderPrinter\Adapter\Command\PrintOrderCommand;
use Veliu\OrderPrinter\Domain\Command\PrintOpenOrdersCommand;
use Veliu\OrderPrinter\Domain\Command\PrintOrderCommand as PrintOrderMessage;

#[CoversClass(PrintOrderCommand::class)]
class PrintOrderCommandTest extends TestCase
{
    private PrintOrderCommand $command;
    private MessageBusInterface&MockObject $messageBus;
    private SymfonyStyle&MockObject $io;

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->messageBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));
        $this->io = $this->createMock(SymfonyStyle::class);
        $this->command = new PrintOrderCommand($this->messageBus);
    }

    public function testInvokeWithAllOpenOrders(): void
    {
        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                return $message instanceof PrintOpenOrdersCommand
                    && true === $message->markInProgress;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->io
            ->expects($this->once())
            ->method('success')
            ->with('All open orders scheduled');

        $result = ($this->command)(
            $this->io,
            orderNumber: null,
            allOpen: true,
            markInProgress: true
        );

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testInvokeWithSpecificOrder(): void
    {
        $orderNumber = 'ORDER123';

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($orderNumber) {
                return $message instanceof PrintOrderMessage
                    && $message->orderNumber === $orderNumber
                    && true === $message->markInProgress;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->io
            ->expects($this->once())
            ->method('success')
            ->with(sprintf('Order "%s" scheduled', $orderNumber));

        $result = ($this->command)(
            $this->io,
            orderNumber: $orderNumber,
            allOpen: false,
            markInProgress: true
        );

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testInvokeWithoutOrderNumberAndAllOpen(): void
    {
        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->io
            ->expects($this->once())
            ->method('error')
            ->with('No order number nor --all-open option provided.');

        $result = ($this->command)(
            $this->io,
            orderNumber: null,
            allOpen: false,
            markInProgress: true
        );

        $this->assertEquals(Command::FAILURE, $result);
    }

    public function testInvokeWithMarkInProgressFalse(): void
    {
        $orderNumber = 'ORDER123';

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($orderNumber) {
                return $message instanceof PrintOrderMessage
                    && $message->orderNumber === $orderNumber
                    && false === $message->markInProgress;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->io
            ->expects($this->once())
            ->method('success')
            ->with(sprintf('Order "%s" scheduled', $orderNumber));

        $result = ($this->command)(
            $this->io,
            orderNumber: $orderNumber,
            allOpen: false,
            markInProgress: false
        );

        $this->assertEquals(Command::SUCCESS, $result);
    }
}
