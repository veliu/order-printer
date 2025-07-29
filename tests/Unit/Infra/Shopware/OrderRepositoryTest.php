<?php

declare(strict_types=1);

namespace Tests\Veliu\OrderPrinter\Infra\Shopware;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Veliu\OrderPrinter\Domain\Address\Address;
use Veliu\OrderPrinter\Domain\Api\ApiResponse;
use Veliu\OrderPrinter\Domain\Api\ClientInterface;
use Veliu\OrderPrinter\Domain\Order\Exception\OrderNotFound;
use Veliu\OrderPrinter\Domain\Order\Order;
use Veliu\OrderPrinter\Infra\Shopware\Api\Order\FindOrderNumbersRequest;
use Veliu\OrderPrinter\Infra\Shopware\Api\Order\UpdateOrderStateRequest;
use Veliu\OrderPrinter\Infra\Shopware\Api\OrderDelivery\FindDeliveriesRequest;
use Veliu\OrderPrinter\Infra\Shopware\OrderRepository;
use Veliu\OrderPrinter\Infra\Shopware\OrderStateEnum;

/** @covers \Veliu\OrderPrinter\Infra\Shopware\OrderRepository */
class OrderRepositoryTest extends TestCase
{
    private ClientInterface|MockObject $client;
    private OrderRepository $repository;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->repository = new OrderRepository($this->client);
    }

    public function testGetByOrderNumberSuccessfully(): void
    {
        $orderNumber = '10001';
        $orderDelivery = $this->createOrderDelivery($orderNumber);

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with($this->isInstanceOf(FindDeliveriesRequest::class))
            ->willReturn(new ApiResponse(200, ['total' => 1, 'data' => [$orderDelivery]]));


        $order = $this->repository->getByOrderNumber($orderNumber);

        $this->assertEquals($orderNumber, $order->number);
        $this->assertEquals('1000,00', $order->totalPrice);
        $this->assertEquals('50,00', $order->shippingCost);
        $this->assertEquals('John Doe', $order->address->name);
        $this->assertCount(1, $order->items);
    }

    public function testGetByOrderNumberThrowsExceptionWhenNotFound(): void
    {
        $orderNumber = '99999';

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with($this->isInstanceOf(FindDeliveriesRequest::class))
            ->willReturn(new ApiResponse(200, ['total' => 0, 'data' => []]));

        $this->expectException(OrderNotFound::class);
        $this->repository->getByOrderNumber($orderNumber);
    }

    public function testMarkInProgress(): void
    {
        $order = new Order(
            'order-id-1',
            '10001',
            '1000,00',
            '50,00',
            new Address('John Doe', 'Test Street 1', 'Test City', '+1234567890'),
            [],
            true
        );

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with($this->isInstanceOf(UpdateOrderStateRequest::class))
            ->willReturn(new ApiResponse(200, []));

        $this->repository->markInProgress($order);
    }

    public function testFindNewNumbers(): void
    {
        $expectedNumbers = ['10001', '10002'];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with($this->isInstanceOf(FindOrderNumbersRequest::class))
            ->willReturn(new ApiResponse(200, ['total' => 1, 'data' => [
                ['orderNumber' => '10001'],
                ['orderNumber' => '10002'],
            ]]));

        $numbers = $this->repository->findNewNumbers();

        $this->assertEquals($expectedNumbers, $numbers);
    }

    private function createOrderDelivery(string $orderNumber): array
    {
        return [
            'order' => [
                'id' => 'order-id-1',
                'orderNumber' => $orderNumber,
                'amountTotal' => 1000.00,
                'lineItems' => [
                    [
                        'type' => 'container',
                        'productNumber' => 'PROD-001',
                        'label' => 'Test Product',
                        'totalPrice' => 100.00,
                        'quantity' => 1,
                        'payload' => [
                            'productNumber' => '21',
                        ],
                    ],
                ],
                'stateMachineState' => [
                    'technicalName' => OrderStateEnum::OPEN->value,
                ],
            ],
            'shippingOrderAddress' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'street' => 'Test Street 1',
                'city' => 'Test City',
                'phoneNumber' => '+1234567890',
            ],
            'shippingCosts' => [
                'totalPrice' => 50.00,
            ],
        ];
    }
}
