<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Api;

trait HasNoAdditionalHeadersTrait
{
    public function getHeaders(): ?array
    {
        return null;
    }
}
