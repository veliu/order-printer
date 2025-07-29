<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Receipt;

final class Receipt
{
    public function __construct(
        public readonly string $orderNumber,
        public readonly string $content,
        private ?string $filePath = null,
    ) {
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /** @psalm-param non-empty-string $filePath */
    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }
}
