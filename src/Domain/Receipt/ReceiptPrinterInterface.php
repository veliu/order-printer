<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Receipt;

/**
 * @psalm-api
 */
interface ReceiptPrinterInterface
{
    public function print(Receipt $receipt): void;
}
