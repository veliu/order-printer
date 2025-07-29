<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Unit\Infra\Printer\Epson;

use PHPUnit\Framework\TestCase;
use Veliu\OrderPrinter\Infra\Printer\Epson\ReceiptFormatter;

/**
 * @covers \Veliu\OrderPrinter\Infra\Printer\Epson\ReceiptFormatter
 */
final class ReceiptFormatterTest extends TestCase
{
    private ReceiptFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ReceiptFormatter();
    }

    public function testInitialize(): void
    {
        $expected = "\x1B\x40\x1B\x74\x13"; // INIT + CODE_PAGE_858
        $this->assertSame($expected, $this->formatter->getContents());
    }

    public function testAddTitle(): void
    {
        $text = 'Test Title';
        $expected = $this->formatter->getContents(). // Initial content
            "\x1B\x61\x01". // ALIGN_CENTER
            "\x1B\x45\x01". // BOLD_ON
            "\x1B\x21\x10". // DOUBLE_WIDTH_ON
            $this->encodeText($text)."\n".
            "\x1B\x21\x00". // DOUBLE_WIDTH_OFF
            "\x1B\x45\x00". // BOLD_OFF
            "\x1B\x61\x00"; // ALIGN_LEFT

        $result = $this->formatter->addTitle($text)->getContents();

        $this->assertSame($expected, $result);
    }

    public function testAddText(): void
    {
        $text = 'Simple text';
        $expected = $this->formatter->getContents(). // Initial content
            "\x1B\x21\x00". // NORMAL_SIZE
            $this->encodeText($text)."\n";

        $result = $this->formatter->addText($text)->getContents();

        $this->assertSame($expected, $result);
    }

    public function testAddBoldText(): void
    {
        $text = 'Bold text';
        $expected = $this->formatter->getContents(). // Initial content
            "\x1B\x45\x01". // BOLD_ON
            $this->encodeText($text)."\n".
            "\x1B\x45\x00"; // BOLD_OFF

        $result = $this->formatter->addBoldText($text)->getContents();

        $this->assertSame($expected, $result);
    }

    public function testAddDivider(): void
    {
        $expected = $this->formatter->getContents(). // Initial content
            str_repeat('-', ReceiptFormatter::DEFAULT_WIDTH)."\n";

        $result = $this->formatter->addDivider()->getContents();

        $this->assertSame($expected, $result);
    }

    public function testAddCenterText(): void
    {
        $text = 'Centered text';
        $expected = $this->formatter->getContents(). // Initial content
            "\x1B\x61\x01". // ALIGN_CENTER
            $this->encodeText($text)."\n".
            "\x1B\x61\x00"; // ALIGN_LEFT

        $result = $this->formatter->addCenterText($text)->getContents();

        $this->assertSame($expected, $result);
    }

    public function testAddTableRow(): void
    {
        $label = 'Item';
        $value = '10.00';

        // Calculate expected spaces
        $spaces = ReceiptFormatter::DEFAULT_WIDTH - (strlen($label) + strlen($value));
        $expected = $this->formatter->getContents(). // Initial content
            $this->encodeText($label).
            str_repeat(' ', $spaces).
            $this->encodeText($value)."\n";

        $result = $this->formatter->addTableRow($label, $value)->getContents();

        $this->assertSame($expected, $result);
    }

    public function testAddTableRowWithLongLabel(): void
    {
        $label = 'This is a very long label that should wrap';
        $value = '10.00';

        $result = $this->formatter->addTableRow($label, $value)->getContents();

        // Verify that the content contains multiple lines
        $this->assertStringContainsString("\n", $result);
        // Verify that the value appears only once (on the first line)
        $this->assertEquals(1, substr_count($result, $this->encodeText($value)));
    }

    public function testAddReverseText(): void
    {
        $text = 'Reverse text';
        $expected = $this->formatter->getContents(). // Initial content
            "\x1D\x42\x01". // REVERSE_ON
            $this->encodeText($text).
            "\x1D\x42\x00". // REVERSE_OFF
            "\n";

        $result = $this->formatter->addReverseText($text)->getContents();

        $this->assertSame($expected, $result);
    }

    public function testFinalize(): void
    {
        $expected = $this->formatter->getContents(). // Initial content
            "\n\n\n\n". // Feed lines
            "\x1B\x64\x03\x1D\x56\x01"; // FEED_AND_CUT

        $result = $this->formatter->finalize()->getContents();

        $this->assertSame($expected, $result);
    }

    public function testAddQRCode(): void
    {
        $data = 'https://example.com';
        $result = $this->formatter->addQRCode($data)->getContents();

        // Verify QR code commands are present
        $this->assertStringContainsString("\x1D(k", $result); // GS ( k
        $this->assertStringContainsString($data, $result);
    }

    public function testMethodChaining(): void
    {
        $result = $this->formatter
            ->addTitle('Title')
            ->addText('Text')
            ->addDivider()
            ->addTableRow('Label', 'Value')
            ->finalize()
            ->getContents();

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    /**
     * Helper method to simulate the encodeText functionality.
     */
    private function encodeText(string $text): string
    {
        return iconv('UTF-8', 'CP858//TRANSLIT', $text);
    }
}
