<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Symfony\FileSystem;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Veliu\OrderPrinter\Domain\Receipt\Receipt;
use Veliu\OrderPrinter\Domain\Receipt\ReceiptSaverInterface;

/** @psalm-api  */
final readonly class ReceiptSaver implements ReceiptSaverInterface
{
    /** @psalm-var non-empty-string */
    private string $fileDirectory;
    private const string DATA_DIR = '/data/receipts/';

    public function __construct(
        /* @psalm-param non-empty-string $projectDir */
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir,
    ) {
        $this->fileDirectory = $projectDir.self::DATA_DIR;
    }

    #[\Override]
    public function save(Receipt $receipt): string
    {
        $filesystem = new Filesystem();
        $now = new \DateTimeImmutable();

        $fileName = sprintf('%s_%s.txt', $receipt->orderNumber, $now->format('Y-m-d_H-i-s'));
        $filePath = sprintf('%s/%s', $this->fileDirectory, $fileName);
        $fileContent = $receipt->content;

        $filesystem->dumpFile($filePath, $fileContent);

        return $filePath;
    }
}
