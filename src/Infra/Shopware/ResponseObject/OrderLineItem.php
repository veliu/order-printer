<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\ResponseObject;

use Psl\Type\TypeInterface;

use function Psl\Type\float;
use function Psl\Type\literal_scalar;
use function Psl\Type\non_empty_string;
use function Psl\Type\positive_int;
use function Psl\Type\shape;
use function Psl\Type\uint;
use function Psl\Type\union;

final readonly class OrderLineItem
{
    /**
     * @psalm-param non-empty-string $label
     * @psalm-param positive-int $quantity
     * @psalm-param non-empty-string $type
     * @psalm-param non-empty-string $productNumber
     */
    public function __construct(
        public string $label,
        public int $quantity,
        public float $totalPrice,
        public string $type,
        public string $productNumber,
    ) {
    }

    public static function fromArray(array $array): self
    {
        $data = self::arrayShape()->coerce($array);

        return new self(
            $data['label'],
            $data['quantity'],
            (float) $data['totalPrice'],
            $data['type'],
            $data['payload']['productNumber'],
        );
    }

    public static function arrayShape(): TypeInterface
    {
        return shape([
            'label' => non_empty_string(),
            'quantity' => positive_int(),
            'totalPrice' => union(float(), uint()),
            'type' => union(literal_scalar('product'), literal_scalar('container')),
            'payload' => shape([
                'productNumber' => non_empty_string(),
            ], allow_unknown_fields: true),
        ], allow_unknown_fields: true);
    }
}
