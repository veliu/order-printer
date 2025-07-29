<?php

namespace Veliu\OrderPrinter\Infra\Shopware\Api;

interface AccessTokenInterface
{
    public function isExpired(\DateTimeImmutable $now): bool;

    public function getAuthorizationHeaderValue(): string;
}
