<?php

declare(strict_types=1);

namespace Async\Request;

//use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\RequestMethodInterface;

interface HyperInterface extends RequestMethodInterface
{
    /**
     * Flush/close properties state, along with the stored `Stream` and `Request` instances.
     */
    public function close();

    public function flush();

    public function logger();

    /**
     * Add/check support for gzip and deflate response content encoding.
     */
    public function withEncoding(): HyperInterface;

    /**
     * Run awaitable HTTP tasks in the requests set concurrently and block until the condition specified by count.
     *
     * This function works similar to `gatherWait()`.
     * Controls how the `wait/fetch` functions operates.
     * `await()` will behave like **Promise** functions `All`, `Some`, `Any` in JavaScript.
     *
     * @param array $requests
     * @param int $count - If set, initiate a competitive race between multiple HTTP tasks.
     * - When amount of tasks as completed, the `wait` will return with HTTP task response.
     * - When `0` (default), will wait for all to complete.
     * @param bool $exception - If `true` (default), the first raised exception is
     * immediately propagated to the task that `yield`ed on wait(). Other awaitables in
     * the aws sequence won't be abort/cancelled and will continue to run.
     * - If `false`, exceptions are treated the same as successful response results,
     * and aggregated in the response list.
     * @param bool $clearAborted - If `true` (default), close/cancel/abort remaining result/responses
     * @return array associative `$httpId` => `$response`
     *
     * @throws \LengthException - If the number of HTTP tasks less than the desired $count.
     */
    public static function await(
        array $requests,
        int $count = 0,
        bool $exception = true,
        bool $clearAborted = true
    );

    /**
     * Run awaitable HTTP tasks in the httpId sequence concurrently.
     *
     * If all awaitables are completed successfully, the result is an aggregate list of returned values.
     * The order of result values corresponds to the order of awaitables in httpId.
     *
     * The first raised exception is immediately propagated to the task that `yield`ed `wait()`.
     * Other awaitables in the sequence won't be aborted/cancelled and will continue to run.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.gather
     *
     * @param array $httpId
     * @return array associative `$taskId` => `$result`
     *
     * @throws \Exception - if not an HTTP task id
     */
    public static function wait(...$httpId);

    /**
     * Create an new HTTP request background task
     *
     * @param \Generator $httpFunction
     * @param HyperInterface $name
     *
     * @return int - request HTTP id
     */
    public static function awaitable(\Generator $httpFunction, HyperInterface $hyper);

    /**
     * Abort/kill and remove an open request task using the `awaitable` HTTP id.
     *
     * @param int $httpId
     * @return bool
     */
    public static function cancel(int $httpId);

    /**
     * Make a GET call.
     *
     * @param string $url - URI for the request.
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function get(string $url, array ...$authorizeHeaderOptions);

    /**
     * Make a POST call.
     *
     * @param string $url - URI for the request.
     * @param \Psr\Http\Message\StreamInterface|array|null $data
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function post(string $url, $data = null, array ...$authorizeHeaderOptions);

    /**
     * Make a HEAD call.
     *
     * @param string $url - URI for the request.
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function head(string $url, array ...$authorizeHeaderOptions);

    /**
     * Make a PATCH call.
     *
     * @param string $url - URI for the request.
     * @param \Psr\Http\Message\StreamInterface|array|null $data
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function patch(string $url, $data = null, array ...$authorizeHeaderOptions);

    /**
     * Make a PUT call.
     *
     * @param string $url - URI for the request.
     * @param \Psr\Http\Message\StreamInterface|mixed|null $data
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function put(string $url, $data = null, array ...$authorizeHeaderOptions);

    /**
     * Make a DELETE call.
     *
     * @param string $url - URI for the request.
     * @param \Psr\Http\Message\StreamInterface|array|null $data
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function delete(string $url, $data = null, array ...$authorizeHeaderOptions);

    /**
     * Make an OPTIONS call.
     *
     * @param string $url - URI for the request.
     * @param array $authorize - ['type' => "", 'username' => "", 'password' => "", 'token' => ""]
     * @param array ...$authorizeHeaderOptions
     * @return ResponseInterface|bool
     */
    public function options(string $url, array ...$authorizeHeaderOptions);

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
    public function sendRequest(RequestInterface $request); // Can't use `ResponseInterface` return type, cause method contains `yield`

    /**
     * Will auto retry sending an PSR-7 request with additional changeable attempts and timeout setting.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface - If an error happens while processing the request.
     */
    public function selectSendRequest(
        RequestInterface $request,
        int $attempts = \RETRY_ATTEMPTS,
        float $timeout = \RETRY_TIMEOUT,
        bool $withTimeout = false
    );
}
