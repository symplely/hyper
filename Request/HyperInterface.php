<?php

declare(strict_types=1);

namespace Async\Request;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ResponseInterface;
use Fig\Http\Message\RequestMethodInterface;

interface HyperInterface extends ClientInterface, RequestMethodInterface
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
    public function head(string $url = null, ...$authorizeHeaderOptions);

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
     * @param array ...$headerOptions
     *
     * @return ResponseInterface
     */
    public function request($method, $url, $body = null, array ...$headerOptions): ResponseInterface;
}
