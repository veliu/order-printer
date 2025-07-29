<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Receipt;

use Veliu\OrderPrinter\Domain\Order\Order;

/** @psalm-api  */
final readonly class ReceiptGenerator implements ReceiptGeneratorInterface
{
    public function __construct(
        private ReceiptFormatterInterface $formatter,
    ) {
    }

    #[\Override]
    public function fromOrder(Order $order): Receipt
    {
        $formatter = $this->formatter
            ->initialize()
            ->addTitle(sprintf('#%s', $order->number))
            ->addDivider()
            ->addText($order->address->name)
            ->addText($order->address->street)
            ->addText($order->address->city)
            ->addText($order->address->phone)
            ->addDivider();

        foreach ($order->items as $item) {
            $label = $item->productNumber.' ';
            $label .= implode(' ', $this->extractExtras($item->label));

            $formatter->addTableRow(
                sprintf('%dx %s', $item->quantity, $label), $item->price.' €'
            );
        }

        $formatter
            ->addDivider()
            ->addTableRow('Versandkosten:', $order->shippingCost.' €')
            ->addTableRow('Gesamt:', $order->totalPrice.' €')
            ->finalize();

        return new Receipt($order->number, $formatter->getContents());
    }

    /**
     * @psalm-param non-empty-string $label
     *
     * @return string[]
     *
     * @psalm-return list<non-empty-string>
     */
    public function extractExtras(string $label): array
    {
        if (preg_match_all('/[+-][^+-]+(?=[+-]|$)/', $label, $matches)) {
            return array_map('trim', $matches[0]);
        }

        return [];
    }
}
