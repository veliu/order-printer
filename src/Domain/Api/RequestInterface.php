<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Api;

interface RequestInterface
{
    public function getMethod(): string;

    public function getUri(): string;

    public function getBody(): ?array;

    public function getHeaders(): ?array;
}
