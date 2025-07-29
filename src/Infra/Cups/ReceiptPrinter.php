<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Cups;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Veliu\OrderPrinter\Domain\Receipt\Receipt;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptPrinterInterface;

/** @psalm-api  */
final readonly class ReceiptPrinter implements ReceiptPrinterInterface
{
    public function __construct(
        #[Autowire(env: 'PRINTER_NAME')]
        private string $printerName,
    ) {
    }

    /**
     * @throws ProcessFailedException
     */
    #[\Override]
    public function print(Receipt $receipt): void
    {
        $process = new Process(['lp', '-d', $this->printerName, $receipt->getFilePath()]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
