<?php

declare(strict_types=1);

namespace Async\Request;

use Async\Request\Uri;
use Async\Request\Request;
use Async\Request\Response;
use Async\Request\AsyncStream;
use Async\Request\Body;
use Async\Request\BodyInterface;
use Async\Request\HyperInterface;
use Async\Request\Exception\NetworkException;
use Async\Request\Exception\RequestException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
     *
     * @var array
     */
    protected $headerOptions = [
        'headers' => [
            'Accept-Charset' => 'utf-8',
            'X-Powered-By' => 'PHP/' . \PHP_VERSION,
        ]
    ];

    /**
     * Default options.
     *
     * @var array
     */
    protected $options = [
        'protocol_version' => 1.1,
        'follow_location' => 1,
        'request_fulluri' => false,
        'max_redirects' => 10,
        'ignore_errors' => true,
        'timeout' => 120,
        'user_agent' => \SYMPLELY_USER_AGENT,
    ];

    protected $requestOptions = null;

    /**
	 * The requested uri
     *
     * @var string
     */
    protected $uri;

    /**
     * headers with lowercase keys
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Stream of data.
     *
     * @var resource|null
     */
    protected $resource;

    protected $instance;

    protected $meta = [];

	/**
	 * The request params
	 *
	 * @var array
	 */
    protected $parameters = [];

    /**
     * Make a GET call.
     *
     * @return ResponseInterface|bool
     */
    public function get(string $url = null, array ...$authorizeHeaderOptions)
    {
        if (empty($url))
            return false;

        $response = yield $this->request(Request::METHOD_GET, $url, null, $this->optionsHeaderSplicer($authorizeHeaderOptions));

        return $response;
    }

    /**
     * Make a POST call.
     *
     * @return ResponseInterface|bool
     */
    public function post(string $url = null, $data = null, array ...$authorizeHeaderOptions)
    {
        if (empty($url))
            return false;

        $response = yield $this->request(Request::METHOD_POST, $url, $data, $this->optionsHeaderSplicer($authorizeHeaderOptions));

        return $response;
    }

    /**
     * Make a HEAD call.
     *
     * @return ResponseInterface|bool
     */
    public function head(string $url = null, array ...$authorizeHeaderOptions)
    {
        if (empty($url))
            return false;

        $options = $this->optionsHeaderSplicer($authorizeHeaderOptions);
        $response = yield $this->request(Request::METHOD_HEAD, $url, null, $options);

        if ($response->getStatusCode() === 405) {
            $response = yield $this->get($url, $options);
        }

        return $response;
    }

    /**
     * Make a PATCH call.
     *
     * @return ResponseInterface|bool
     */
    public function patch(string $url = null, $data = null, array ...$authorizeHeaderOptions)
    {
        if (empty($url))
            return false;

        $response = yield $this->request(Request::METHOD_PATCH, $url, $data, $this->optionsHeaderSplicer($authorizeHeaderOptions));

        return $response;
    }

    /**
     * Make a PUT call.
     *
     * @return ResponseInterface|bool
     */
    public function put(string $url = null, $data = null, array ...$authorizeHeaderOptions)
    {
        if (empty($url))
            return false;

        $response = yield $this->request(Request::METHOD_PUT, $url, $data, $this->optionsHeaderSplicer($authorizeHeaderOptions));

        return $response;
    }

    /**
     * Make a DELETE call.
     *
     * @return ResponseInterface|bool
     */
    public function delete(string $url = null, $data = null, array ...$authorizeHeaderOptions)
    {
        if (empty($url))
            return false;

        $response = yield $this->request(Request::METHOD_DELETE, $url, $data, $this->optionsHeaderSplicer($authorizeHeaderOptions));

        return $response;
    }

    /**
     * Make an OPTIONS call.
     *
     * @return ResponseInterface|bool
     */
    public function options(string $url = null, array ...$authorizeHeaderOptions)
    {
        $response = yield $this->request(Request::METHOD_OPTIONS, $url, null, $this->optionsHeaderSplicer($authorizeHeaderOptions));

        return $response;
    }

    public function request($method, $url, $body = null, array ...$headerOptions)
    {
        if (isset($headerOptions[0]) && isset($headerOptions[1]))
            [$headers, $options] = $headerOptions;
        elseif (isset($headerOptions[0]))
            [$headers, $options] = [$headerOptions, null];
        else
            [$headers, $options] = null;

        // Build out URI instance
        if (!$url instanceof UriInterface) {
            $url = Uri::create($url);
        }

        // Create a new Request
        $request = (new Request)
			->withMethod($method)
			->withUri($url);

        // Set default HTTP version
        $request = $request->withProtocolVersion((string) $this->options['protocol_version']);

        // Add in default headers to request.
        if (!empty($this->headerOptions['headers'])) {
            foreach($this->headerOptions['headers'] as $name => $value) {
                $request = $request->withAddedHeader($name, $value);
            }
        }

        // Add in default User-Agent header if none was provided.
        if ($request->hasHeader('User-Agent') === false) {
            $request = $request->withHeader('User-Agent', \SYMPLELY_USER_AGENT);
        }

        if (!empty($options)) {
            $this->requestOptions = \array_merge($this->options, $options);
        }

		if (\is_array($body)) {
            [$type, $data, $format] = (isset($body[0]) && \is_string($body[0])) ? $body : [Body::FORM, $body];
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

        // Add request specific headers.
        if (!empty($headers['headers'])) {
            foreach($headers['headers'] as $key => $value) {
                $request = $request->withHeader($key, $value);
            }
        }

        return $this->sendRequest($request);
    }

    /**
     * @inheritdoc
     */
    public function sendRequest(RequestInterface $request)
    {
        return $this->send($request);
    }

    protected function send(RequestInterface $request)
    {
        if ($request->getBody()->getSize()) {
			$request = $request->withHeader('Content-Length', (string) $request->getBody()->getSize());
        }

        $useOptions = \is_array($this->requestOptions) ? $this->requestOptions : $this->options;
		$options = \array_merge($useOptions, [
			'method' => $request->getMethod(),
			'protocol_version' => $request->getProtocolVersion(),
			'header' => $this->buildRequestHeaders($request->getHeaders()),
		]);

        $context = ['http' => $options];

        if ($request->getBody()->getSize()) {
            $context['http']['content'] = yield $request->getBody()->__toString();
        }

        $resource = @\fopen($request->getUri()->__toString(), 'rb', false, \stream_context_create($context));

        if (!\is_resource($resource)) {
            $error = \error_get_last()['message'];
            if (\strpos($error, 'getaddrinfo') || \strpos($error, 'Connection refused')) {
                $e = new NetworkException($error, $request);
            } else {
                $e = new RequestException($request, $error, 0);
            }

            throw $e;
        }

        $stream = yield AsyncStream::copyResource($resource);

        $headers = \stream_get_meta_data($resource)['wrapper_data'] ?? [];

        if ($options['follow_location']) {
            $headers = $this->filterResponseHeaders($headers);
        }

        \fclose($resource);

        $parts = \explode(' ', \array_shift($headers), 3);
        $version = \explode('/', $parts[0])[1];
        $status = (int)$parts[1];

        $response = Response::create($status)
            ->withProtocolVersion($version)
            ->withBody($stream);

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

    protected function authorization(array $authorize): string
    {
        $authorization = '';
        if (isset($authorize['type'])) {
            if ($authorize['type'] =='basic' && !empty($authorize['username']) && !empty($authorize['password'])) {
                $authorization = 'Basic ' . \base64_encode($authorize['username'] . ':' . $authorize['password']);
            } elseif ($authorize['type'] =='bearer' && !empty($authorize['token'])) {
                $authorization = 'Bearer ' . $authorize['token'];
            } elseif ($authorize['type']=='digest' && !empty($authorize['username'])) {
                $authorization = 'Digest ';
                foreach ($authorize as $k => $v) {
                    if (empty($k) || empty($v))
                        continue;

                    if ($k == 'password')
                        continue;

                    $authorization .= $k . '="' . $v . '", ';
                }
            }
        }

        return $authorization;
    }

	protected function optionsHeaderSplicer(...$headersOptions): array
	{
        if (isset($headersOptions[0])) {
            $authorization = $this->authorization($headersOptions[0]);
            $authorize = !empty($authorization) ? ['Authorization' => $authorization] : null;
            array_shift($headersOptions);
        } else {
            $authorize = null;
        }

        [$headers, $options] = (isset($headersOptions[0])) ? $headersOptions : null;
		return [['headers' => [ $authorize, $headers]], $options];
    }

	protected function flush()
	{
        $this->requestOptions = null;
        $this->uri = null;
        $this->headers = [];
        $this->resource = null;
        $this->instance -null;
        $this->meta = [];
        $this->parameters = [];
    }

	public function close()
	{
        $this->flush();
	}
}
