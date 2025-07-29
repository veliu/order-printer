<?php

namespace Veliu\OrderPrinter\Domain\Api;

/**
 * @psalm-api
 */
interface CustomEncoderInterface
{
    public function encode(array $data): string;
}
