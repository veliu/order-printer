<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Api;

/**
 * @psalm-api
 */
interface ApiResponseInterface
{
    /**
     * @psalm-return positive-int
     */
    public function getStatusCode(): int;

    public function getBody(): array;
}
