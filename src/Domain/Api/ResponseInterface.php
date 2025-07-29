<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Api;

use Psl\Type\TypeInterface;

/**
 * @template T of ResponseInterface
 */
interface ResponseInterface
{
    /**
     * @psalm-return T
     */
    public static function fromResponse(ApiResponseInterface $response): self;

    /**
     * @psalm-return T
     */
    public static function fromArray(array $array): self;

    public static function type(): TypeInterface;
}
