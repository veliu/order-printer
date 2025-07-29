<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Printer\Epson;

use Veliu\OrderPrinter\Domain\Receipt\ReceiptFormatterInterface;

final class ReceiptFormatter implements ReceiptFormatterInterface
{
    // ESC/POS Commands
    private const string ESC = "\x1B";
    private const string GS = "\x1D";
    private const string INIT = "\x1B\x40"; // Initialize printer
    private const string CUT = "\x1D\x56\x41"; // Paper cut
    private const string BOLD_ON = "\x1B\x45\x01";
    private const string BOLD_OFF = "\x1B\x45\x00";
    private const string DOUBLE_WIDTH_ON = "\x1B\x21\x10";
    private const string DOUBLE_WIDTH_OFF = "\x1B\x21\x00";
    private const string ALIGN_LEFT = "\x1B\x61\x00";
    private const string ALIGN_CENTER = "\x1B\x61\x01";
    private const string ALIGN_RIGHT = "\x1B\x61\x02";
    private const string NORMAL_SIZE = "\x1B\x21\x00";
    private const string CODE_PAGE_858 = "\x1B\x74\x13"; // For Euro symbol and German characters
    private const string FEED_AND_CUT = "\x1B\x64\x03\x1D\x56\x01"; // Feed 3 lines then cut
    private const string REVERSE_ON = "\x1D\x42\x01";  // White text on black background
    private const string REVERSE_OFF = "\x1D\x42\x00"; // Normal text (black on white)
    private const int MINIMUM_SPACING = 1;
    public const int DEFAULT_WIDTH = 42;
    public string $content = '';

    /** @psalm-api */
    public function __construct()
    {
        $this->initialize();
    }

    private function clear(): void
    {
        $this->content = '';
    }

    #[\Override]
    public function initialize(): self
    {
        $this->clear();
        $this->content .= self::INIT;
        $this->content .= self::CODE_PAGE_858;

        return $this;
    }

    private function encodeText(string $text): string
    {
        return iconv('UTF-8', 'CP858//TRANSLIT', $text);
    }

    #[\Override]
    public function addTitle(string $text): self
    {
        $this->content .= self::ALIGN_CENTER;
        $this->content .= self::BOLD_ON;
        $this->content .= self::DOUBLE_WIDTH_ON;
        $this->content .= $this->encodeText($text)."\n";
        $this->content .= self::DOUBLE_WIDTH_OFF;
        $this->content .= self::BOLD_OFF;
        $this->content .= self::ALIGN_LEFT;

        return $this;
    }

    #[\Override]
    public function addText(string $text): self
    {
        $this->content .= self::NORMAL_SIZE;
        $this->content .= $this->encodeText($text)."\n";

        return $this;
    }

    #[\Override]
    public function addBoldText(string $text): self
    {
        $this->content .= self::BOLD_ON;
        $this->content .= $this->encodeText($text)."\n";
        $this->content .= self::BOLD_OFF;

        return $this;
    }

    #[\Override]
    public function addDivider(): self
    {
        $this->content .= str_repeat('-', self::DEFAULT_WIDTH)."\n";

        return $this;
    }

    #[\Override]
    public function addCenterText(string $text): self
    {
        $this->content .= self::ALIGN_CENTER;
        $this->content .= $this->encodeText($text)."\n";
        $this->content .= self::ALIGN_LEFT;

        return $this;
    }

    #[\Override]
    public function addTableRow(string $label, string $value, int $width = self::DEFAULT_WIDTH): self
    {
        $label = $this->encodeText($label);
        $value = $this->encodeText($value);
        $valueWidth = strlen($value);
        $maxLabelWidth = $width - $valueWidth - self::MINIMUM_SPACING;

        if (strlen($label) <= $maxLabelWidth) {
            return $this->handleSingleLineLabel($label, $value, $width);
        }

        return $this->handleMultiLineLabel($label, $value, $maxLabelWidth, $width);
    }

    private function handleSingleLineLabel(string $label, string $value, int $width): self
    {
        $spaces = $width - (strlen($label) + strlen($value));
        $this->addFormattedLine($label, $spaces, $value);

        return $this;
    }

    private function handleMultiLineLabel(string $label, string $value, int $maxLabelWidth, int $width): self
    {
        $words = explode(' ', $label);
        $currentLine = '';
        $isFirstLine = true;

        foreach ($words as $word) {
            $testLine = $currentLine ? "$currentLine $word" : $word;

            if (strlen($testLine) <= $maxLabelWidth) {
                $currentLine = $testLine;
                continue;
            }

            if ($currentLine) {
                $this->outputLine($currentLine, $value, $width, $isFirstLine);
                $isFirstLine = false;
                $currentLine = $word;
            }
        }

        if ($currentLine) {
            $this->outputLine($currentLine, $value, $width, $isFirstLine);
        }

        return $this;
    }

    private function outputLine(string $text, string $value, int $width, bool $isFirstLine): void
    {
        if ($isFirstLine) {
            $spaces = $width - (strlen($text) + strlen($value));
            $this->addFormattedLine($text, $spaces, $value);
        } else {
            $this->addFormattedLine($text, 0, '');
        }
    }

    private function addFormattedLine(string $text, int $spaces, string $value): void
    {
        $words = explode(' ', $text);
        foreach ($words as $i => $word) {
            if ($i > 0) {
                $this->content .= ' ';
            }
            if (str_starts_with($word, '+') || str_starts_with($word, '-')) {
                $this->content .= self::REVERSE_ON.$word.self::REVERSE_OFF;
            } else {
                $this->content .= $word;
            }
        }
        if ($spaces > 0) {
            $this->content .= str_repeat(' ', $spaces).$value;
        }
        $this->content .= "\n";
    }

    #[\Override]
    public function addQRCode(string $data): self
    {
        // QR Code size (1-16)
        $size = 8;

        // GS ( k pL pH cn fn n (QR Code: Select the model)
        // cn = 49 -> Function 165
        // fn = 65 -> QR Code
        // n = 50 -> Model 2
        $this->content .= self::GS.'(k'.chr(4).chr(0).chr(49).chr(65).chr(50).chr(0);

        // Size of module
        $this->content .= self::GS.'(k'.chr(3).chr(0).chr(49).chr(67).chr($size);

        // Store data in QR code storage area
        $length = strlen($data) + 3;
        $pL = $length % 256;
        $pH = intdiv($length, 256);
        $this->content .= self::GS.'(k'.chr($pL).chr($pH).chr(49).chr(80).chr(48).$data;

        // Print QR code from storage area
        $this->content .= self::GS.'(k'.chr(3).chr(0).chr(49).chr(81).chr(48);

        return $this;
    }

    #[\Override]
    public function addReverseText(string $text): self
    {
        $this->content .= self::REVERSE_ON; // Turn on reverse mode (white text on black background)
        $this->content .= $this->encodeText($text);
        $this->content .= self::REVERSE_OFF; // Turn off reverse mode
        $this->content .= "\n";

        return $this;
    }

    #[\Override]
    public function finalize(): self
    {
        $this->content .= "\n\n\n\n";  // Feed lines
        $this->content .= self::FEED_AND_CUT;    // Cut paper

        return $this;
    }

    #[\Override]
    public function getContents(): string
    {
        return $this->content;
    }
}
