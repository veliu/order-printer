<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Infra\Shopware\Api;

use Psl\Type\TypeInterface;
use Veliu\OrderPrinter\Domain\Api\ApiResponse;
use Veliu\OrderPrinter\Domain\Api\Exception\ClientException;
use Veliu\OrderPrinter\Domain\Api\Exception\NotFoundException;
use Veliu\OrderPrinter\Domain\Api\Exception\ServerException;
use Veliu\OrderPrinter\Domain\Api\Exception\UnexpectedResponseException;
use Veliu\OrderPrinter\Domain\Api\ResponseHandlerInterface;

use function Psl\Json\encode;
use function Psl\Type\shape;
use function Psl\Type\string;
use function Psl\Type\vec;

/** @psalm-api  */
final readonly class ResponseHandler implements ResponseHandlerInterface
{
    private const array SUCCESS_CODES = [200, 201, 204];
    private const string LINE_SEPARATOR = "\r\n";

    #[\Override]
    public function handle(int $statusCode, array $body = []): ApiResponse
    {
        if (in_array($statusCode, self::SUCCESS_CODES, true)) {
            return new ApiResponse($statusCode, $body);
        }

        if (404 === $statusCode) {
            $detail = $body['errors'][0]['detail'] ?? 'Resource not found';
            throw new NotFoundException(sprintf('Not Found Error: %s', $detail));
        }

        if ($statusCode >= 500) {
            throw new ServerException($statusCode, sprintf('Server Error: %s', $this->get500ErrorMessageIfExists($body)));
        }

        if ($statusCode >= 400) {
            throw new ClientException($statusCode, sprintf('Client Error: %s', $this->getErrorMessage($body)));
        }

        throw new UnexpectedResponseException($statusCode, $body);
    }

    private function getErrorMessage(array $body): string
    {
        if (!self::getErrorType()->matches($body)) {
            return encode($body);
        }

        return $this->formatErrors($body['errors'], fn (array $error, int $index) => sprintf(
            '%d. Error: %s, Source: %s',
            $index + 1,
            $error['detail'],
            $error['source']['pointer']
        )
        );
    }

    private function get500ErrorMessageIfExists(array $body): string
    {
        if (!self::get500ErrorType()->matches($body)) {
            return encode($body);
        }

        return $this->formatErrors($body['errors'], fn (array $error, int $index) => sprintf(
            '%d. Error: %s, Code: %s',
            $index + 1,
            $error['detail'],
            $error['code']
        )
        );
    }

    private function formatErrors(array $errors, callable $formatter): string
    {
        return implode(
            self::LINE_SEPARATOR,
            array_map($formatter, $errors, array_keys($errors))
        );
    }

    public static function getErrorType(): TypeInterface
    {
        return shape([
            'errors' => vec(shape([
                'detail' => string(),
                'source' => shape([
                    'pointer' => string(),
                ]),
            ], true)),
        ]);
    }

    public static function get500ErrorType(): TypeInterface
    {
        return shape([
            'errors' => vec(shape([
                'detail' => string(),
                'code' => string(),
            ], true)),
        ]);
    }
}
