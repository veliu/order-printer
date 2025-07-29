<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Api;

final readonly class ApiResponse implements ApiResponseInterface
{
    /**
     * @psalm-param positive-int $statusCode
     */
    public function __construct(
        private int $statusCode,
        private array $body = [],
    ) {
    }

    #[\Override]
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    #[\Override]
    public function getBody(): array
    {
        return $this->body;
    }
}
