<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Receipt;

enum ReceiptPositionPrintTypeEnum: string
{
    case LABEL = 'label';
    case NUMBER = 'number';
}
