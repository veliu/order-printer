<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\Api;

interface AccessTokenProviderInterface
{
    public function provideAccessToken(): AccessTokenInterface;
}
