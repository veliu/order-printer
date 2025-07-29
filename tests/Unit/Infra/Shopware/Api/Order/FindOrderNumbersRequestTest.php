<?php

declare(strict_types=1);

namespace Tests\Veliu\OrderPrinter\Infra\Shopware\Api\Order;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Veliu\OrderPrinter\Infra\Shopware\Api\Order\FindOrderNumbersRequest;
use Veliu\OrderPrinter\Infra\Shopware\OrderStateEnum;

/**
 * @covers \Veliu\OrderPrinter\Infra\Shopware\Api\Order\FindOrderNumbersRequest
 */
final class FindOrderNumbersRequestTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $state = OrderStateEnum::OPEN;
        $request = new FindOrderNumbersRequest($state);

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/search/order', $request->getUri());
        $this->assertNull($request->getHeaders());
    }

    #[DataProvider('orderStateProvider')]
    public function testGetBody(OrderStateEnum $state): void
    {
        $request = new FindOrderNumbersRequest($state);
        $body = $request->getBody();

        $this->assertIsArray($body);
        $this->assertArrayHasKey('includes', $body);
        $this->assertArrayHasKey('filter', $body);
        $this->assertArrayHasKey('associations', $body);

        $this->assertEquals(['order' => ['orderNumber']], $body['includes']);
        $this->assertCount(1, $body['filter']);

        $filter = $body['filter'][0];
        $this->assertEquals('equals', $filter['type']);
        $this->assertEquals('stateMachineState.technicalName', $filter['field']);
        $this->assertEquals($state->value, $filter['value']);

        $this->assertEquals(['stateMachineState' => []], $body['associations']);
    }

    public static function orderStateProvider(): array
    {
        return [
            'open state' => [OrderStateEnum::OPEN],
            'process state' => [OrderStateEnum::PROCESS],
            'complete state' => [OrderStateEnum::COMPLETE],
            'cancel state' => [OrderStateEnum::CANCEL],
            'reopen state' => [OrderStateEnum::REOPEN],
        ];
    }
}
