<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware;

use Veliu\OrderPrinter\Domain\Address\Address;
use Veliu\OrderPrinter\Domain\Api\ClientInterface;
use Veliu\OrderPrinter\Domain\Order\Exception\OrderNotFound;
use Veliu\OrderPrinter\Domain\Order\Order;
use Veliu\OrderPrinter\Domain\Order\OrderItem;
use Veliu\OrderPrinter\Domain\Order\OrderRepositoryInterface;
use Veliu\OrderPrinter\Infra\Shopware\Api\Order\FindOrderNumbersRequest;
use Veliu\OrderPrinter\Infra\Shopware\Api\Order\FindOrderNumbersResponse;
use Veliu\OrderPrinter\Infra\Shopware\Api\Order\UpdateOrderStateRequest;
use Veliu\OrderPrinter\Infra\Shopware\Api\OrderDelivery\FindDeliveriesRequest;
use Veliu\OrderPrinter\Infra\Shopware\Api\OrderDelivery\FindDeliveriesResponse;
use Veliu\OrderPrinter\Infra\Shopware\ResponseObject\OrderDelivery;
use Veliu\OrderPrinter\Infra\Shopware\ResponseObject\OrderLineItem;

/** @psalm-api  */
final readonly class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private ClientInterface $client,
    ) {
    }

    #[\Override]
    public function markInProgress(Order $order): void
    {
        $this->client->request(new UpdateOrderStateRequest($order->identifier, OrderStateEnum::PROCESS));
    }

    #[\Override]
    public function getByOrderNumber(string $number): Order
    {
        $response = FindDeliveriesResponse::fromResponse(
            $this->client->request(new FindDeliveriesRequest(orderNumber: $number))
        );

        if (!$delivery = $response->first()) {
            throw new OrderNotFound($number);
        }

        return self::transform($delivery);
    }

    #[\Override]
    public function findNewNumbers(): array
    {
        $response = FindOrderNumbersResponse::fromResponse(
            $this->client->request(new FindOrderNumbersRequest(state: OrderStateEnum::OPEN))
        );

        return $response->orderNumbers;
    }

    private static function transform(OrderDelivery $orderDelivery): Order
    {
        $containerLineItems = self::removeItemWhenContainerExists($orderDelivery->order->lineItems);

        $address = new Address(
            sprintf(
                '%s %s',
                $orderDelivery->shippingOrderAddress->firstName,
                $orderDelivery->shippingOrderAddress->lastName
            ),
            $orderDelivery->shippingOrderAddress->street,
            $orderDelivery->shippingOrderAddress->city,
            $orderDelivery->shippingOrderAddress->phoneNumber,
        );

        return new Order(
            $orderDelivery->order->id,
            $orderDelivery->order->orderNumber,
            $orderDelivery->order->customerComment,
            number_format($orderDelivery->order->amountTotal, 2, ',', ''),
            number_format($orderDelivery->shippingCosts->totalPrice, 2, ',', ''),
            $address,
            array_map(fn (OrderLineItem $item) => self::transformItem($item), $containerLineItems),
            OrderStateEnum::OPEN === $orderDelivery->order->stateMachineState->technicalName,
            $orderDelivery->shippingMethod->name,
            $orderDelivery->order->createdAt,
        );
    }

    /**
     * @param OrderLineItem[] $allOrderLineItems
     *
     * @return OrderLineItem[]
     */
    private static function removeItemWhenContainerExists(array $allOrderLineItems): array
    {
        $items = [];

        // Group items by product number
        $groupedByProductNumber = [];
        foreach ($allOrderLineItems as $item) {
            // Skip extras (products starting with 'E')
            if (str_starts_with($item->productNumber, 'E')) {
                continue;
            }

            $groupedByProductNumber[$item->productNumber][] = $item;
        }

        // Process each product number group
        foreach ($groupedByProductNumber as $productNumber => $itemsGroup) {
            $containerItem = null;
            $regularItem = null;

            // Find container and regular items
            foreach ($itemsGroup as $item) {
                if ('container' === $item->type) {
                    $containerItem = $item;
                } else {
                    $regularItem = $item;
                }
            }

            // Prefer container if it exists
            if (null !== $containerItem) {
                // Adjust the label: replace the regular product name with the product number
                if (null !== $regularItem) {
                    // Find what's different between container and regular label
                    // Container: "Pizza Margherita +Käse", Regular: "Pizza Margherita"
                    // Result should be: "21 +Käse"
                    $newLabel = str_replace($regularItem->label, (string) $productNumber, $containerItem->label);
                    $containerItem = new OrderLineItem(
                        $newLabel,
                        $containerItem->quantity,
                        $containerItem->totalPrice,
                        $containerItem->type,
                        $containerItem->productNumber,
                    );
                } else {
                    // No regular item to compare, just use product number
                    $containerItem = new OrderLineItem(
                        (string) $productNumber,
                        $containerItem->quantity,
                        $containerItem->totalPrice,
                        $containerItem->type,
                        $containerItem->productNumber,
                    );
                }
                $items[$productNumber] = $containerItem;
            } elseif (null !== $regularItem) {
                // No container, replace entire label with product number
                $regularItem = new OrderLineItem(
                    (string) $productNumber,
                    $regularItem->quantity,
                    $regularItem->totalPrice,
                    $regularItem->type,
                    $regularItem->productNumber,
                );
                $items[$productNumber] = $regularItem;
            }
        }

        return $items;
    }

    private static function transformItem(OrderLineItem $item): OrderItem
    {
        return new OrderItem(
            $item->productNumber,
            $item->label,
            number_format($item->totalPrice, 2, ',', ''),
            $item->quantity
        );
    }
}
