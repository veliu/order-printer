<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Api;

use Veliu\OrderPrinter\Domain\Api\Exception\ResponseException;

/**
 * @psalm-api
 */
interface ResponseHandlerInterface
{
    /**
     * @throws ResponseException
     */
    public function handle(int $statusCode, array $body = []): ApiResponseInterface;
}
