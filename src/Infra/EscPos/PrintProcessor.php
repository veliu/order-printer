<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\EscPos;

use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\MultiplePrintConnector;
use Mike42\Escpos\Printer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Veliu\OrderPrinter\Domain\Address\Address;
use Veliu\OrderPrinter\Domain\Order\Order;
use Veliu\OrderPrinter\Domain\Order\OrderItem;
use Veliu\OrderPrinter\Domain\Order\OrderRepositoryInterface;
use Veliu\OrderPrinter\Domain\Service\PrintOrderProcessorInterface;

/** @psalm-api  */
final readonly class PrintProcessor implements PrintOrderProcessorInterface
{
    /** @psalm-var non-empty-string */
    private string $fileDirectory;

    /** @psalm-api  */
    public function __construct(
        #[Autowire(env: 'PRINTER_NAME')]
        private string $printerName,
        #[Autowire(env: 'DATA_DIR')]
        string $dataDirectory,
        /* @psalm-param non-empty-string $projectDir */
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir,
        private OrderRepositoryInterface $orderRepository,
    ) {
        $this->fileDirectory = $projectDir.$dataDirectory;
    }

    #[\Override]
    public function __invoke(Order $order, bool $markInProgress): void
    {
        $file = $this->fileDirectory.sprintf('%s_%s.txt', $order->number, $order->createdAt->format('Y-m-d_H-i-s'));

        $connector = new MultiplePrintConnector(
            new FilePrintConnector($this->printerName),
            new FilePrintConnector($file),
        );

        $printer = new Printer($connector);
        $printer->initialize();
        $printer->setTextSize(1, 1);
        $printer = $this->setHeader($printer, $order);
        $printer = $this->setAddress($printer, $order->address);
        $printer = $this->setOrderItems($printer, $order->items);
        $printer = $this->setTotals($printer, $order);

        $printer->feed(2);
        $printer->cut(Printer::CUT_PARTIAL);
        $printer->close();

        if ($markInProgress && $order->isNew) {
            $this->orderRepository->markInProgress($order);
        }
    }

    private function setHeader(Printer $printer, Order $order): Printer
    {
        $printer->text(sprintf("Bestellnummer: %s\n", $order->number));
        $printer->text(sprintf("Bestellzeitpunkt: %s\n", $order->createdAt->format('d.m.Y H:i:s')));
        if ($customerComment = $order->customerComment) {
            $printer->text($customerComment."\n");
        }
        $printer->text($this->getDivider());

        return $printer;
    }

    private function setAddress(Printer $printer, Address $address): Printer
    {
        $printer->text(sprintf("%s\n", $address->name));
        $printer->text(sprintf("%s\n", $address->street));
        $printer->text(sprintf("%s\n", $address->city));
        $printer->text(sprintf("%s\n", $address->phone));

        $printer->text($this->getDivider());

        return $printer;
    }

    /**
     * @param OrderItem[] $items
     *
     * @psalm-param non-empty-list<OrderItem> $items
     */
    private function setOrderItems(Printer $printer, array $items): Printer
    {
        foreach ($items as $item) {
            $leftColumn = sprintf('%dx %s', $item->quantity, $item->label);
            $rightColumn = sprintf('%s €', $item->price);
            $this->addTwoColumnsRow($printer, $leftColumn, $rightColumn);
        }

        $printer->text($this->getDivider());

        return $printer;
    }

    private function setTotals(Printer $printer, Order $order): Printer
    {
        $this->addTwoColumnsRow($printer, 'Lieferkosten:', $order->shippingCost.' €');
        $this->addTwoColumnsRow($printer, 'Gesamt:', $order->totalPrice.' €');

        return $printer;
    }

    private function addTwoColumnsRow(Printer $printer, string $leftColumn, string $rightColumn, int $width = 42): void
    {
        $valueWidth = mb_strlen($rightColumn);
        $maxLabelWidth = $width - $valueWidth - 1; // 1 is minimum spacing

        if (mb_strlen($leftColumn) <= $maxLabelWidth) {
            $this->printSingleLineRow($printer, $leftColumn, $rightColumn, $width);

            return;
        }

        $this->printMultiLineRow($printer, $leftColumn, $rightColumn, $maxLabelWidth, $width);
    }

    private function printSingleLineRow(Printer $printer, string $leftColumn, string $rightColumn, int $width): void
    {
        $words = explode(' ', $leftColumn);
        $processedLength = 0;

        foreach ($words as $i => $word) {
            if ($i > 0) {
                $printer->text(' ');
                ++$processedLength;
            }

            if (str_starts_with($word, '+') || str_starts_with($word, '-')) {
                $printer->setReverseColors(true);
                $printer->text($word);
                $printer->setReverseColors(false);
            } else {
                $printer->text($word);
            }
            $processedLength += mb_strlen($word);
        }

        $spacing = $width - ($processedLength + mb_strlen($rightColumn));
        $printer->text(str_repeat(' ', max(1, $spacing)));
        $printer->text($rightColumn."\n");
    }

    private function printMultiLineRow(Printer $printer, string $leftColumn, string $rightColumn, int $maxLabelWidth, int $width): void
    {
        $words = explode(' ', $leftColumn);
        $currentLine = '';
        $isFirstLine = true;

        foreach ($words as $word) {
            $testLine = $currentLine ? "$currentLine $word" : $word;

            if (mb_strlen($testLine) <= $maxLabelWidth) {
                $currentLine = $testLine;
                continue;
            }

            if ($currentLine) {
                if ($isFirstLine) {
                    // First line includes the right column
                    $this->printSingleLineRow($printer, $currentLine, $rightColumn, $width);
                    $isFirstLine = false;
                } else {
                    // Continuation lines are indented and don't include the right column
                    $printer->text('    '); // 4 spaces indent
                    $this->printSingleLineRow($printer, $currentLine, '', $width);
                }
                $currentLine = $word;
            }
        }

        if ($currentLine) {
            if ($isFirstLine) {
                $this->printSingleLineRow($printer, $currentLine, $rightColumn, $width);
            } else {
                $printer->text('    '); // 4 spaces indent
                $this->printSingleLineRow($printer, $currentLine, '', $width);
            }
        }
    }

    private function getDivider(): string
    {
        return str_repeat('-', 42)."\n";
    }
}
