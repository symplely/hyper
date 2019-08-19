<?php

declare(strict_types=1);

namespace Async\Request;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\RequestMethodInterface;

interface HyperInterface extends RequestMethodInterface
{
    /**
     * @param string $url - URI for the request.
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function get(string $url = null, array ...$authorizeHeaderOptions);

    /**
     * @param string $url - URI for the request.
     * @param \Psr\Http\Message\StreamInterface|array|null $data
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function post(string $url = null, $data = null, array ...$authorizeHeaderOptions);

    /**
     * @param string $url - URI for the request.
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function head(string $url = null, array ...$authorizeHeaderOptions);

    /**
     * @param string $url - URI for the request.
     * @param \Psr\Http\Message\StreamInterface|array|null $data
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function patch(string $url = null, $data = null, array ...$authorizeHeaderOptions);

    /**
     * @param string $url - URI for the request.
     * @param \Psr\Http\Message\StreamInterface|mixed|null $data
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function put(string $url = null, $data = null, array ...$authorizeHeaderOptions);

    /**
     * @param string $url - URI for the request.
     * @param \Psr\Http\Message\StreamInterface|array|null $data
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function delete(string $url = null, $data = null, array ...$authorizeHeaderOptions);

    /**
     * Make an OPTIONS call.
     *
     * @param string $url - URI for the request.
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function options(string $url = null, array ...$authorizeHeaderOptions);

    /**
     * Make an request
     *
     * @param string $method - GET, POST, HEAD, PUT, PATCH, DELETE, OPTION
     * @param Uri|string $url - URI for the request.
     * @param \Psr\Http\Message\StreamInterface|array|null $body
     * @param array ...$authorizeHeaderOptions
     *
     * @return RequestInterface
     */
    public function request($method, $url, $body = null, array ...$authorizeHeaderOptions): RequestInterface;

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request);
}
