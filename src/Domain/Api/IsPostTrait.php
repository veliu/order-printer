<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Api;

trait IsPostTrait
{
    public function getMethod(): string
    {
        return 'POST';
    }
}
