<?php

declare(strict_types=1);

namespace Async\Request;

use Async\Coroutine\Kernel;
use Async\Coroutine\CoroutineInterface;
use Async\Coroutine\TaskInterface;
use Async\Request\Uri;
use Async\Request\Request;
use Async\Request\Response;
use Async\Request\AsyncStream;
use Async\Request\Body;
use Async\Request\BodyInterface;
use Async\Request\HyperInterface;
use Async\Request\Exception\ClientException;
use Async\Request\Exception\NetworkException;
use Async\Request\Exception\RequestException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Hyper
 *
 * @package Async\Request\Hyper
 */
class Hyper implements HyperInterface
{
    /**
     * Set of key => value pairs to include as default headers with request calls.
     *
     * Headers are only added when using the `request` method
     * (or any of the built-in HTTP method calls: get, post, put, etc.).
     */
    const HEADERS = [
        'headers' => [
            'Accept' => '*/*',
            'Accept-Charset' => 'utf-8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'X-Powered-By' => 'PHP/' . \PHP_VERSION,
            'Connection' => 'close'
        ]
    ];

    /**
     * Default options.
     */
    const OPTIONS = [
        'protocol_version' => '1.1',
        'follow_location' => 1,
        'request_fulluri' => false,
        'max_redirects' => 10,
        'ignore_errors' => true,
        'timeout' => 2,
        'user_agent' => \SYMPLELY_USER_AGENT,
    ];

    /**
     * @var StreamInterface
     */
    protected $stream = null;

    /**
     * @var RequestInterface
     */
    protected $request = null;

    /**
     * Value to be used with `stream_set_timeout()`
     *
     * @var float
     */

    protected $timeout = \RETRY_TIMEOUT;

    protected $httpId = null;

    /**
     * @var string;
     */
    protected $loggerName = '';

    /**
     * @var LoggerInterface;
     */
    protected $logger = null;

    public function __construct(?string $loggerName = null)
    {
        $this->loggerName = empty($loggerName) ? '-' : $loggerName;
        $this->logger = \hyper_logger($this->loggerName);
        if (empty($loggerName)) {
            \logger_array(0xff, 1, null, $this->loggerName);
        }
    }

    public function close()
    {
        if ($this->stream instanceof StreamInterface)
            $this->stream->close();

        $this->flush();
    }

    public function flush()
    {
        $this->request = null;
        $this->stream = null;
        $this->httpId = null;
        $this->timeout = \RETRY_TIMEOUT;

        $this->logger = null;
        $this->loggerName = '';
    }

    /**
     * Return the Logger instance and Logger name used
     *
     * @return array<LoggerInterface, string>
     */
    public function logger(): array
    {
        return [$this->logger, $this->loggerName];
    }

    /**
     * @inheritdoc
     */
    public static function waitOptions(int $count = 0, bool $exception = true, bool $clearAborted = true): void
    {
        Kernel::gatherOptions($count, $exception, $clearAborted);
    }

    /**
     * Setup wait/gather to run and wait until requested count is reached.
     *
     * @return void
     */
    protected static function waitController()
    {
        /**
         * Check and handle request tasks already completed before entering/executing, fetch()/wait().
         */
        $onAlreadyCompleted = function (TaskInterface $tasks) {
            $tasks->customState('ended');
            $hyper = $tasks->getCustomData();
            if ($hyper instanceof HyperInterface)
                $hyper->flush();

            return $tasks->result();
        };

        /**
         * Handle not started tasks, force start.
         */
        $onRequestNotStarted = function (TaskInterface $tasks, CoroutineInterface $coroutine) {
            try {
                if (($tasks->getState() === 'running') || $tasks->rescheduled()) {
                    $coroutine->execute(true);
                } elseif ($tasks->isCustomState('beginning') && !$tasks->completed()) {
                    $coroutine->schedule($tasks);
                    $coroutine->execute(true);
                }

                if ($tasks->completed() || $tasks->erred()) {
                    $tasks->customState();
                }
            } catch (\Throwable $error) {
                $tasks->setState('erred');
                $tasks->setException($error);
                $coroutine->schedule($tasks);
                $coroutine->execute(true);
            }
        };

        /**
         * Handle finished tasks
         */
        $onCompletedRequests = function (TaskInterface $tasks) {
            $tasks->customState('ended');
            $hyper = $tasks->getCustomData();
            if ($hyper instanceof HyperInterface)
                $hyper->flush();

            return $tasks->result();
        };

        /**
         * When updating current/running task list, abort/close responses/requests that will not be used.
         */
        $onRequestsToClear = function (TaskInterface $tasks) {
            $tasks->customState('aborted');
            $hyper = $tasks->getCustomData();
            if ($hyper instanceof HyperInterface)
                $hyper->close();
        };

        /**
         * Handle error tasks.
         */
        $onError = null;

        /**
         * Handle cancel tasks.
         */
        $onCancel = null;

        Kernel::gatherController(
            'beginning',
            $onAlreadyCompleted,
            $onRequestNotStarted,
            $onCompletedRequests,
            $onError,
            $onCancel,
            $onRequestsToClear
        );
    }

    /**
     * @inheritdoc
     */
    public static function wait(...$httpId)
    {
        self::waitController();
        return Kernel::gather(...$httpId);
    }

    /**
     * @inheritdoc
     */
    public static function awaitable(\Generator $httpFunction, HyperInterface $hyper)
    {
        return Kernel::await($httpFunction, 'beginning', $hyper);
    }

    /**
     * @inheritdoc
     */
    public static function cancel(int $httpId)
    {
        return Kernel::cancelTask($httpId, 'aborted', \BAD_ID);
    }

    public function selectSendRequest(
        RequestInterface $request,
        int $attempts = \RETRY_ATTEMPTS,
        float $timeout = \RETRY_TIMEOUT,
        bool $withTimeout = false
    ) {
        if ($attempts > 0) {
            $this->timeout = ($withTimeout) ? $timeout : \REQUEST_TIMEOUT;
            try {
                $response = yield $this->sendRequest(($withTimeout)
                    ? $request->withOptions(['timeout' => $timeout])
                    : $request->withOptions(['timeout' => \REQUEST_TIMEOUT])
                );
            } catch (ClientException $requestError) {
                $error = $requestError->getMessage();
                if (\strpos($error, 'respond') || (\strpos($error, 'failed to open stream') && $attempts === \RETRY_ATTEMPTS)) {
                    $attempts--;
                    $timeout = $timeout * \RETRY_MULTIPLY;
                    yield \log_debug(
                        'On task: {taskId} {class}, Retry: {attempts} Timeout: {timeout} Exception: {exception}',
                        ['taskId' => $this->httpId, 'class' => __METHOD__, 'attempts' => $attempts, 'timeout' =>  $timeout, 'exception' => $requestError],
                        $this->loggerName
                    );

                    $response = yield $this->selectSendRequest($request, $attempts, $timeout, true);
                } else {
                    yield \log_error(
                        'On task: {taskId} {class}, Timeout: {timeout} Exception: {exception}',
                        ['taskId' => $this->httpId, 'class' => __METHOD__, 'timeout' =>  $timeout, 'exception' => $requestError],
                        $this->loggerName
                    );

                    // Throw, if this method wasn't called by `request/awaitable`, `fetch/wait`,
                    // or any `http_*` functions. The task id value should be anything beside 1, 2 or null.
                    if (($this->httpId === 1) || ($this->httpId === 2) || ($this->httpId === null)) {
                        throw $requestError;
                    }


                    $response = $requestError;
                }
            }

            return $response;
        }

        return;
    }

    /**
     * @inheritdoc
     */
    public function get(string $url, array ...$authorizeHeaderOptions)
    {
        return yield $this->selectSendRequest(
            $this->request(Request::METHOD_GET, $url, null, $authorizeHeaderOptions)
        );
    }

    /**
     * @inheritdoc
     */
    public function post(string $url, $data = null, array ...$authorizeHeaderOptions)
    {
        return yield $this->selectSendRequest(
            $this->request(Request::METHOD_POST, $url, $data, $authorizeHeaderOptions)
        );
    }

    /**
     * @inheritdoc
     */
    public function head(string $url, array ...$authorizeHeaderOptions)
    {
        $response = yield $this->selectSendRequest(
            $this->request(Request::METHOD_HEAD, $url, null, $authorizeHeaderOptions)
        );

        if (($response instanceof \Throwable) || $response->getStatusCode() === 405) {
            $response = yield $this->selectSendRequest(
                $this->request(Request::METHOD_GET, $url, null, $authorizeHeaderOptions)
            );
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function patch(string $url, $data = null, array ...$authorizeHeaderOptions)
    {
        return yield $this->selectSendRequest(
            $this->request(Request::METHOD_PATCH, $url, $data, $authorizeHeaderOptions)
        );
    }

    /**
     * @inheritdoc
     */
    public function put(string $url, $data = null, array ...$authorizeHeaderOptions)
    {
        return yield $this->selectSendRequest(
            $this->request(Request::METHOD_PUT, $url, $data, $authorizeHeaderOptions)
        );
    }

    /**
     * @inheritdoc
     */
    public function delete(string $url, $data = null, array ...$authorizeHeaderOptions)
    {
        return yield $this->selectSendRequest(
            $this->request(Request::METHOD_DELETE, $url, $data, $authorizeHeaderOptions)
        );
    }

    /**
     * @inheritdoc
     */
    public function options(string $url, array ...$authorizeHeaderOptions)
    {
        return yield $this->selectSendRequest(
            $this->request(Request::METHOD_OPTIONS, $url, null, $authorizeHeaderOptions),
            3,
            5,
            true
        );
    }

    /**
     * @inheritdoc
     */
    public function request($method, $url, $body = null, array ...$authorizeHeaderOptions): RequestInterface
    {
        $headerOptions = $this->optionsHeaderSplicer($authorizeHeaderOptions);
        $defaultOptions = self::OPTIONS;
        $defaultHeaders = self::HEADERS;

        $headers = $options = [];
        $index = 0;
        \array_map(function ($sections) use (&$headers, &$options, &$index) {
            $index++;
            if ($index == 1) {
                $headers = (isset($sections['headers'][0])) ? [] : $sections;
            } else {
                $options = \array_merge($options, $sections);
            }
        }, $headerOptions);

        // Build out URI instance
        if (!$url instanceof UriInterface) {
            $url = Uri::create($url);
        }

        // Create a new Request
        $request = (new Request)
            ->withMethod($method)
            ->withUri($url);

        // Set default HTTP version
        $request = $request->withProtocolVersion((string) $defaultOptions['protocol_version']);

        // Add in default headers to request.
        if (!empty($defaultHeaders['headers'])) {
            foreach ($defaultHeaders['headers'] as $name => $value) {
                $request = $request->withAddedHeader($name, $value);
            }
        }

        // Add in default User-Agent header if none was provided.
        if ($request->hasHeader('User-Agent') === false) {
            $request = $request->withHeader('User-Agent', \SYMPLELY_USER_AGENT);
        }

        // Add requested specific options..
        if (!empty($options)) {
            // Add with defaults also.
            $useOptions = \array_merge($defaultOptions, $options);
            $request = $request->withOptions($useOptions);
        }

        // Add request specific headers.
        if (!empty($headers['headers'])) {
            foreach ($headers['headers'] as $key => $value) {
                $request = $request->withHeader($key, $value);
            }
        }

        if (\is_array($body)) {
            $index = 0;
            $type = '';
            $data = [];
            $format = null;
            foreach ($body as $key => $value) {
                $index++;
                if ($index == 1) {
                    $type = ($key === 0) && \is_string($value) ? $value : Body::FORM;
                    $data = \is_string($key) ? [$key => $value] : $value;
                } elseif ($index == 2) {
                    $data = ($key === 0) || \is_array($value) ? $value : [$key => $value];
                } elseif ($index == 3) {
                    $format = $value;
                }
            };

            $body = new Body($type, $data, $format);
        }

        // Add body and Content-Type header
        if ($body) {
            if ($body instanceof BodyInterface && $request->hasHeader('Content-Type') === false) {
                $request = $request->withHeader("Content-Type", $body->getContentType());
            }

            if ($request->hasHeader('Content-Length') === false) {
                $request = $request->withHeader("Content-Length", (string) $body->getSize());
            }

            $request = $request->withBody($body);
        }
        $this->request = $request;

        return $request;
    }

    /**
     * @inheritdoc
     */
    public function sendRequest(RequestInterface $request) // Can't use `ResponseInterface` return type, cause method contains `yield`
    {
        $option = self::OPTIONS;
        $method = $request->getMethod();

        if ($request->getBody()->getSize()) {
            $request = $request->withHeader('Content-Length', (string) $request->getBody()->getSize());
        }

        $useOption = $request->getOptions();
        $useOptions = empty($useOptions) ? $option : $useOption;
        $options = \array_merge($useOptions, [
            'method' => $method,
            'protocol_version' => $request->getProtocolVersion(),
            'header' => $this->buildRequestHeaders($request->getHeaders()),
        ]);

        $context = [
            'http' => $options,
            'ssl' => [
                'disable_compression' => true
            ]
        ];

        if ($request->getBody()->getSize()) {
            if ($request->getBody() instanceof BodyInterface)
                $context['http']['content'] = $request->getBody()->__toString();
            else
                $context['http']['content'] = yield $request->getBody()->getContents();
        }

        $ctx = \stream_context_create($context);
        if ($request->debugging()) {
            \stream_context_set_params($ctx, array('notification' => [$request, 'debug']));
        }

        $url = $request->getUri()->__toString();
        yield;

        $start = \microtime(true);
        $resource = @\fopen($url, 'rb', false, $ctx);
        $timer = \microtime(true) - $start;

        if (empty($this->httpId)) {
            $this->httpId = yield Kernel::taskId();
        }

        if (!\is_resource($resource)) {
            $error = \error_get_last()['message'];
            if (\strpos($error, 'getaddrinfo') || \strpos($error, 'Connection refused')) {
                $e = new NetworkException($error, $request);
            } else {
                $e = new RequestException($request, $error, 0);
            }

            yield \log_error(
                'On task: {taskId} {class}, failed In: {timer}ms on Timeout: {timeout} with Exception: {exception}',
                ['taskId' => $this->httpId, 'class' => __METHOD__, 'timer' => $timer, 'timeout' => $this->timeout, 'exception' => $e],
                $this->loggerName
            );

            throw $e;
        }

        yield \log_info(
            'On task: {taskId} {class}, {method} {url} Timeout: {timeout} Took: {timer}ms',
            ['taskId' => $this->httpId, 'class' => __METHOD__, 'method' => $method, 'url' => $url, 'timeout' => $this->timeout, 'timer' => $timer],
            $this->loggerName
        );

        $stream = AsyncStream::createFromResource($resource);
        if (!\stream_set_timeout($resource, (int) ($this->timeout * \RETRY_MULTIPLY))) {
            $stream->close();
            $e = new RequestException($request, \error_get_last()['message'], 0);
            yield \log_warning(
                'On task: {taskId} {class}, {method} {url} failed to Set: {timeout} Exception: {exception}',
                ['taskId' => $this->httpId, 'class' => __METHOD__, 'method' => $method, 'url' => $url, 'timeout' => ($this->timeout * \RETRY_MULTIPLY), 'exception' => $e],
                $this->loggerName
            );

            throw $e;
        }

        $headers = \stream_get_meta_data($resource)['wrapper_data'];

        // Add task id to stream instance
        $this->stream = $stream->taskPid($this->httpId);

        if ($option['follow_location']) {
            $headers = $this->filterResponseHeaders($headers);
        }

        $parts = \explode(' ', \array_shift($headers), 3);
        $version = \explode('/', $parts[0])[1];
        $status = (int) $parts[1];

        yield;
        if (($method == Request::METHOD_HEAD) || ($method == Request::METHOD_OPTIONS)) {
            $response = Response::create($status)
                ->withProtocolVersion($version);
        } else {
            $response = Response::create($status)
                ->withProtocolVersion($version)
                ->withBody($stream);
        }

        foreach ($this->buildResponseHeaders($headers) as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }

    /**
     * Build the request headers.
     *
     * @param array $requestHeaders
     * @return array<string>
     */
    protected function buildRequestHeaders(array $requestHeaders): array
    {
        $headers = [];

        foreach ($requestHeaders as $key => $values) {
            foreach ($values as $value) {
                $headers[] = "{$key}: {$value}";
            }
        }

        return $headers;
    }

    /**
     * Build the response headers.
     *
     * @param array $lines
     * @return array<string>
     */
    protected function buildResponseHeaders(array $lines): array
    {
        $headers = [];
        foreach ($lines as $line) {
            $parts = \explode(':', $line, 2);
            $headers[\trim($parts[0])][] = \trim($parts[1] ?? null);
        }

        return $headers;
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    protected function filterResponseHeaders(array $headers): array
    {
        $filteredHeaders = [];
        foreach ($headers as $header) {
            if (strpos($header, 'HTTP/') === 0) {
                $filteredHeaders = [];
            }

            $filteredHeaders[] = $header;
        }

        return $filteredHeaders;
    }

    protected function authorization(array $authorize = null): string
    {
        if (empty($authorize))
            return '';

        $authorization = '';
        if (isset($authorize['auth_basic']) && isset($authorize['auth_basic'][0]) && isset($authorize['auth_basic'][1])) {
            // HTTP Basic authentication with a username and a password
            $authorization = 'Basic ' . \base64_encode($authorize['auth_basic'][0] . ':' . $authorize['auth_basic'][1]);
        } elseif (isset($authorize['auth_basic']) && isset($authorize['auth_basic'][0])) {
            // HTTP Basic authentication with only the username and not a password
            $authorization = 'Basic ' . \base64_encode($authorize['auth_basic'][0]);
        } elseif (isset($authorize['auth_bearer'])) {
            // HTTP Bearer authentication (also called token authentication)
            $authorization = 'Bearer ' . $authorize['auth_bearer'];
        } elseif (isset($authorize['auth_digest']) && isset($authorize['auth_digest'][0])) {
            $authorization = 'Digest ';
            foreach ($authorize as $k => $v) {
                if (empty($k) || empty($v))
                    continue;

                if ($k == 'password')
                    continue;

                $authorization .= $k . '="' . $v . '", ';
            }
        }

        return $authorization;
    }

    protected function optionsHeaderSplicer(array ...$headersOptions): array
    {
        $headersOptions = $headersOptions[0];
        $header['headers'] = $authorizer = $headers = $options = [];
        if (isset($headersOptions[0][0])) {
            $temp = \array_shift($headersOptions);
            if (empty($headersOptions))
                $headersOptions = $temp;
        }

        $index = 0;
        if (\is_array($headersOptions)) {
            \array_map(function ($sections) use (&$authorizer, &$headers, &$options, &$index) {
                $index++;
                if ($index == 1) {
                    if (!empty($sections)) {
                        $authorization = $this->authorization($sections);
                        $authorizer = !empty($authorization) ? ['Authorization' => $authorization] : [];
                    }
                } elseif ($index == 2) {
                    $headers = $sections;
                } else {
                    $options = \array_merge($options, $sections);
                }
            }, $headersOptions);
        }

        if (!empty($authorizer))
            $combined = \array_merge($authorizer, $headers);
        else
            $combined = $headers;

        if (!empty($combined))
            $header['headers'] = $combined;

        return !empty($header['headers']) ? [$header, $options] : [[], $options];
    }
}
