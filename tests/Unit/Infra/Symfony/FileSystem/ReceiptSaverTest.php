<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Unit\Infra\Symfony\FileSystem;

use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Symfony\Component\Filesystem\Filesystem;
use Veliu\OrderPrinter\Domain\Receipt\Receipt;
use Veliu\OrderPrinter\Infra\Symfony\FileSystem\ReceiptSaver;

/**
 * @covers \Veliu\OrderPrinter\Infra\Symfony\FileSystem\ReceiptSaver
 */
class ReceiptSaverTest extends TestCase
{
    private const string PROJECT_DIR = '/tmp/test';
    private const string DATA_DIR = '/data/receipts';
    private ReceiptSaver $receiptSaver;
    private Filesystem $filesystem;

    #[\Override]
    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->receiptSaver = new ReceiptSaver(self::PROJECT_DIR);

        // Ensure the directory exists
        $this->filesystem->mkdir(self::PROJECT_DIR.self::DATA_DIR);
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Cleanup after tests
        if ($this->filesystem->exists(self::PROJECT_DIR)) {
            $this->filesystem->remove(self::PROJECT_DIR);
        }
    }

    public function testSaveReceiptCreatesFileWithCorrectContent(): void
    {
        // Arrange
        $receipt = new Receipt(
            orderNumber: 'ORDER123',
            content: 'Test receipt content'
        );

        // Act
        $filePath = $this->receiptSaver->save($receipt);

        // Assert
        $this->assertTrue($this->filesystem->exists($filePath));
        $this->assertStringContainsString('ORDER123', $filePath);
        $this->assertStringContainsString('.txt', $filePath);
        $this->assertEquals('Test receipt content', file_get_contents($filePath));
    }

    public function testSaveReceiptGeneratesUniqueFileNames(): void
    {
        // Arrange
        $receipt = new Receipt(
            orderNumber: 'ORDER123',
            content: 'Test receipt content'
        );

        // Act
        $filePath1 = $this->receiptSaver->save($receipt);
        \Psl\Async\sleep(Duration::seconds(1));
        $filePath2 = $this->receiptSaver->save($receipt);

        // Assert
        $this->assertNotEquals($filePath1, $filePath2);
        $this->assertTrue($this->filesystem->exists($filePath1));
        $this->assertTrue($this->filesystem->exists($filePath2));
    }

    public function testSaveReceiptReturnsAbsoluteFilePath(): void
    {
        // Arrange
        $receipt = new Receipt(
            orderNumber: 'ORDER123',
            content: 'Test receipt content'
        );

        // Act
        $filePath = $this->receiptSaver->save($receipt);

        // Assert
        $this->assertStringStartsWith(self::PROJECT_DIR.self::DATA_DIR, $filePath);
    }
}
