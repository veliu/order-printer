<?php

namespace Veliu\OrderPrinter\Domain\Receipt;

/**
 * @psalm-api
 */
interface ReceiptSaverInterface
{
    /**
     * Returns file path.
     *
     * @psalm-return non-empty-string
     */
    public function save(Receipt $receipt): string;
}
