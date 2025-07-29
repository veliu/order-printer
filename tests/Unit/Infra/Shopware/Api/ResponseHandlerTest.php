<?php

declare(strict_types=1);

namespace Veliu\OrderPrinter\Tests\Unit\Infra\Shopware\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Veliu\OrderPrinter\Domain\Api\ApiResponse;
use Veliu\OrderPrinter\Domain\Api\Exception\ClientException;
use Veliu\OrderPrinter\Domain\Api\Exception\NotFoundException;
use Veliu\OrderPrinter\Domain\Api\Exception\ServerException;
use Veliu\OrderPrinter\Domain\Api\Exception\UnexpectedResponseException;
use Veliu\OrderPrinter\Infra\Shopware\Api\ResponseHandler;

/** @covers \Veliu\OrderPrinter\Infra\Shopware\Api\ResponseHandler */
class ResponseHandlerTest extends TestCase
{
    private ResponseHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ResponseHandler();
    }

    #[DataProvider('successStatusCodesProvider')]
    public function testHandlesSuccessfulResponses(int $statusCode): void
    {
        $body = ['data' => ['foo' => 'bar']];

        $response = $this->handler->handle($statusCode, $body);

        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($body, $response->getBody());
    }

    public function testHandlesNotFoundError(): void
    {
        $body = [
            'errors' => [
                ['detail' => 'Resource not found message'],
            ],
        ];

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Not Found Error: Resource not found message');

        $this->handler->handle(404, $body);
    }

    public function testHandlesServerError(): void
    {
        $body = [
            'errors' => [
                [
                    'detail' => 'Internal server error',
                    'code' => 'INTERNAL_ERROR',
                ],
            ],
        ];

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Server Error: 1. Error: Internal server error, Code: INTERNAL_ERROR');

        $this->handler->handle(500, $body);
    }

    public function testHandlesClientError(): void
    {
        $body = [
            'errors' => [
                [
                    'detail' => 'Validation failed',
                    'source' => ['pointer' => '/data/attributes/name'],
                ],
            ],
        ];

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Client Error: 1. Error: Validation failed, Source: /data/attributes/name');

        $this->handler->handle(400, $body);
    }

    public function testHandlesUnexpectedResponse(): void
    {
        $this->expectException(UnexpectedResponseException::class);

        $this->handler->handle(300, []);
    }

    public function testHandlesMalformedErrorBody(): void
    {
        $body = ['unexpected' => 'structure'];

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Server Error: {"unexpected":"structure"}');

        $this->handler->handle(500, $body);
    }

    public static function successStatusCodesProvider(): array
    {
        return [
            [200],
            [201],
            [204],
        ];
    }
}
