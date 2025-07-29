<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\Api\OAuth;

use Psl\Type\TypeInterface;
use Veliu\OrderPrinter\Domain\Api\ApiResponseInterface;

use function Psl\Type\positive_int;
use function Psl\Type\shape;
use function Psl\Type\string;

final readonly class AccessTokenResponse
{
    public function __construct(
        public string $tokenType,
        public int $expiresIn,
        public string $accessToken,
    ) {
    }

    public static function fromResponse(ApiResponseInterface $response): self
    {
        $body = self::type()->coerce($response->getBody());

        return new self(
            $body['token_type'],
            $body['expires_in'],
            $body['access_token'],
        );
    }

    private static function type(): TypeInterface
    {
        return shape([
            'token_type' => string(),
            'expires_in' => positive_int(),
            'access_token' => string(),
        ], true);
    }
}
