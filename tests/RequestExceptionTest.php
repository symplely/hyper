<?php

namespace Async\Tests;

use Async\Request\Request;
use Async\Request\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use PHPUnit\Framework\TestCase;

class RequestExceptionTest extends TestCase
{
    public function test_request_exception_returns_request_instance()
    {
        $requestException = new RequestException(
            new Request('GET', "https://www.google.com"),
            "Bad Request",
            400
        );

        $this->assertTrue($requestException->getRequest() instanceof RequestInterface);
    }
}