<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Domain\Api;

use Veliu\OrderPrinter\Domain\Api\Exception\ResponseException;

interface ClientInterface
{
    /**
     * @throws ResponseException
     */
    public function request(RequestInterface $request, ?CustomEncoderInterface $customEncoder = null): ApiResponseInterface;

    /**
     * @psalem-return  non-empty-string
     */
    public function getApiHost(): string;
}
