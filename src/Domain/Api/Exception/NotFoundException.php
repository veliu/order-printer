<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Api\Exception;

class NotFoundException extends ClientException
{
    public function __construct(string $message)
    {
        parent::__construct(404, $message);
    }
}
