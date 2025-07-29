<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\Api;

use Veliu\OrderPrinter\Domain\Api\RequestInterface;

interface StoreApiRequest extends RequestInterface
{
    /** @psalm-return non-empty-string */
    public function getAccessKey(): string;
}
