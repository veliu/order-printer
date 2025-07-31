<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Unit\Infra\Shopware\Api\OrderDelivery;

use PHPUnit\Framework\TestCase;
use Veliu\OrderPrinter\Infra\Shopware\Api\OrderDelivery\FindDeliveriesRequest;
use Veliu\OrderPrinter\Infra\Shopware\OrderStateEnum;

/** @covers \Veliu\OrderPrinter\Infra\Shopware\Api\OrderDelivery\FindDeliveriesRequest */
final class FindDeliveriesRequestTest extends TestCase
{
    public function testGetUri(): void
    {
        $request = new FindDeliveriesRequest();
        self::assertSame('/api/search/order-delivery', $request->getUri());
    }

    public function testGetBodyWithoutParameters(): void
    {
        $request = new FindDeliveriesRequest();
        $expectedBody = [
            'includes' => [
                'order_delivery' => ['id', 'order', 'shippingCosts', 'shippingOrderAddress'],
                'order' => ['id', 'orderNumber', 'amountTotal', 'lineItems', 'stateMachineState', 'createdAt'],
                'order_line_item' => ['id', 'label', 'quantity', 'totalPrice', 'type', 'payload'],
                'order_address' => ['id', 'firstName', 'lastName', 'street', 'zipcode', 'city', 'phoneNumber'],
                'state_machine_state' => ['technicalName'],
                'calculated_price' => ['totalPrice'],
            ],
            'filter' => [
                [
                    'type' => 'equals',
                    'field' => 'order.lineItems.type',
                    'value' => 'container',
                ],
            ],
            'associations' => [
                'order' => [
                    'associations' => ['lineItems' => [], 'stateMachineState' => []],
                ],
                'shippingOrderAddress' => [],
            ],
        ];

        self::assertEquals($expectedBody, $request->getBody());
    }

    public function testGetBodyWithOrderNumber(): void
    {
        $request = new FindDeliveriesRequest(orderNumber: 'ORDER123');
        $body = $request->getBody();

        self::assertCount(2, $body['filter']);
        self::assertEquals([
            'type' => 'equals',
            'field' => 'order.orderNumber',
            'value' => 'ORDER123',
        ], $body['filter'][1]);
    }

    public function testGetBodyWithState(): void
    {
        $request = new FindDeliveriesRequest(state: OrderStateEnum::COMPLETE);
        $body = $request->getBody();

        self::assertCount(2, $body['filter']);
        self::assertEquals([
            'type' => 'equals',
            'field' => 'order.stateMachineState.technicalName',
            'value' => OrderStateEnum::COMPLETE->value,
        ], $body['filter'][1]);
    }

    public function testGetBodyWithBothParameters(): void
    {
        $request = new FindDeliveriesRequest(
            orderNumber: 'ORDER123',
            state: OrderStateEnum::COMPLETE
        );
        $body = $request->getBody();

        self::assertCount(3, $body['filter']);
        self::assertEquals([
            'type' => 'equals',
            'field' => 'order.stateMachineState.technicalName',
            'value' => OrderStateEnum::COMPLETE->value,
        ], $body['filter'][1]);
        self::assertEquals([
            'type' => 'equals',
            'field' => 'order.orderNumber',
            'value' => 'ORDER123',
        ], $body['filter'][2]);
    }
}
