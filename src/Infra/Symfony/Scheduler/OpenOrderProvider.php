<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Symfony\Scheduler;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Veliu\OrderPrinter\Domain\Command\PrintOpenOrdersCommand;

/** @psalm-suppress UnusedClass */
#[AsSchedule]
final readonly class OpenOrderProvider implements ScheduleProviderInterface
{
    #[\Override]
    public function getSchedule(): Schedule
    {
        return new Schedule()->add(
            RecurringMessage::every('10 seconds', new PrintOpenOrdersCommand(true))
        );
    }
}
