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
     */
    const HEADERS = [
        'headers' => [
            'Accept' => '*/*',
            'Accept-Charset' => 'utf-8',
            'X-Powered-By' => 'PHP/' . \PHP_VERSION,
            'Connection' => 'close',
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
        'timeout' => 30,
        'user_agent' => \SYMPLELY_USER_AGENT,
    ];

    /**
     * Make a GET call.
     *
     * @return ResponseInterface|bool
     */
    public function get(string $url = null, array ...$authorizeHeaderOptions)
    {
        if (empty($url))
            return false;

        $response = yield $this->request(Request::METHOD_GET, $url, null, $authorizeHeaderOptions);

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

        $response = yield $this->request(Request::METHOD_POST, $url, $data, ...$authorizeHeaderOptions);

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

        $response = yield $this->request(Request::METHOD_HEAD, $url, null, $authorizeHeaderOptions);

        if ($response->getStatusCode() === 405) {
            $response = yield $this->get($url, $authorizeHeaderOptions);
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

        $response = yield $this->request(Request::METHOD_PATCH, $url, $data, $authorizeHeaderOptions);

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

        $response = yield $this->request(Request::METHOD_PUT, $url, $data, $authorizeHeaderOptions);

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

        $response = yield $this->request(Request::METHOD_DELETE, $url, $data, $authorizeHeaderOptions);

        return $response;
    }

    /**
     * Make an OPTIONS call.
     *
     * @return ResponseInterface|bool
     */
    public function options(string $url = null, array ...$authorizeHeaderOptions)
    {
        $response = yield $this->request(Request::METHOD_OPTIONS, $url, null, $authorizeHeaderOptions);

        return $response;
    }

    public function request($method, $url, $body = null, array ...$authorizeHeaderOptions)
    {
        $headerOptions = $this->optionsHeaderSplicer($authorizeHeaderOptions);
        $defaultOptions = self::OPTIONS;
        $defaultHeaders = self::HEADERS;

        $headers = $options = [];
        $index = 0;
        \array_map(function($sections) use(&$headers, &$options , &$index) {
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
            foreach($defaultHeaders['headers'] as $name => $value) {
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
            foreach($headers['headers'] as $key => $value) {
                $request = $request->withHeader($key, $value);
            }
        }

		if (\is_array($body)) {
            $format = null;
            if (isset($body[0]) && isset($body[1]) && isset($body[2]))
                [$type, $data, $format] = $body;
            elseif (isset($body[0]) && isset($body[1]))
                [$type, $data] = $body;
            else
                [$type, $data] = [Body::FORM, $body];

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

        return $this->sendRequest($request);
    }

    /**
     * @inheritdoc
     */
    public function sendRequest(RequestInterface $request)
    {
        $option = self::OPTIONS;

        if ($request->getBody()->getSize()) {
			$request = $request->withHeader('Content-Length', (string) $request->getBody()->getSize());
        }

        $useOption = $request->getOptions();
        $useOptions = empty($useOptions) ? $option : $useOption;
		$options = \array_merge($useOptions, [
			'method' => $request->getMethod(),
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
        if ($option['follow_location']) {
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
        if (empty($headersOptions))
            return [];

        $headersOptions = $headersOptions[0];
        $header['headers'] = $authorizer = $headers = $options = [];
        if (isset($headersOptions[0][0])) {
            $temp = \array_shift($headersOptions);
            if (empty($headersOptions))
                $headersOptions = $temp;
        }

        $index = 0;
        if (\is_array($headersOptions)) {
            \array_map(function($sections) use(&$authorizer, &$headers, &$options, &$index) {
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
