<?php

namespace Veliu\OrderPrinter\Domain\Receipt;

/**
 * @psalm-api
 */
interface ReceiptFormatterInterface
{
    public const int DEFAULT_WIDTH = 42;

    /** @psalm-param non-empty-string $text */
    public function addTitle(string $text): self;

    /** @psalm-param non-empty-string $text */
    public function addText(string $text): self;

    /** @psalm-param non-empty-string $text */
    public function addReverseText(string $text): self;

    /** @psalm-param non-empty-string $text */
    public function addBoldText(string $text): self;

    public function addDivider(): self;

    /** @psalm-param non-empty-string $text */
    public function addCenterText(string $text): self;

    public function addTableRow(string $label, string $value, int $width = self::DEFAULT_WIDTH): self;

    /** @psalm-param non-empty-string $data */
    public function addQRCode(string $data): self;

    public function finalize(): self;

    /** @psalm-return non-empty-string */
    public function getContents(): string;

    public function initialize(): self;
}
