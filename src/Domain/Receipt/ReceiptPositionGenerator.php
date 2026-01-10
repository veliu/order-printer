<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Receipt;

use Veliu\OrderPrinter\Domain\Order\OrderItem;

final readonly class ReceiptPositionGenerator
{
    /** @psalm-return non-empty-string */
    public function __invoke(OrderItem $item): string
    {
        return self::getNumberOrLabel($item);
    }

    private static function getNumberOrLabel(OrderItem $item): string
    {
        return match ($item->receiptPositionPrintType) {
            ReceiptPositionPrintTypeEnum::NUMBER => self::replaceBaseNameWithNumber($item->label, $item->productNumber),
            ReceiptPositionPrintTypeEnum::LABEL => $item->label,
        };
    }

    /**
     * Replaces the base product name at the start of the label with a number.
     *
     * Examples:
     *  "Pizza Mix +Knoblauch" => "26 +Knoblauch"
     *  "Pizza Mix"            => "26"
     *  "Pizza Mix (mild)"     => "26 (mild)"
     */
    private static function replaceBaseNameWithNumber(string $label, string $number): string
    {
        $pattern = '/^\s*.*?(?=(?:\s[+\-(]|\s*$))/u';

        return preg_replace($pattern, $number, $label, 1) ?? $label;
    }
}
