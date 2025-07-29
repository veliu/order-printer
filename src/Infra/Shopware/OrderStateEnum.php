<?php

namespace Veliu\OrderPrinter\Infra\Shopware;

enum OrderStateEnum: string
{
    case OPEN = 'open';
    case PROCESS = 'process';
    case COMPLETE = 'complete';
    case CANCEL = 'cancel';
    case REOPEN = 'reopen';

    /** @psalm-param non-empty-string $technicalName */
    public static function fromTechnicalName(string $technicalName): self
    {
        return match ($technicalName) {
            'open' => self::OPEN,
            'in_progress' => self::PROCESS,
            'completed' => self::COMPLETE,
            'cancelled' => self::CANCEL,
        };
    }
}
