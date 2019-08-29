<?php

declare(strict_types = 1);

use Async\Request\Hyper;
use Async\Request\Request;
use Async\Request\HyperInterface;
use Async\Request\BodyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

if(!\function_exists('mime_content_type')) {
    function mime_content_type($filename)
    {
        $mime_types = array(
            '3gp'     => 'video/3gpp',
            '7z'      => 'application/x-7z-compressed',
            'aac'     => 'audio/x-aac',
            'ai'      => 'application/postscript',
            'aif'     => 'audio/x-aiff',
            'asc'     => 'text/plain',
            'asf'     => 'video/x-ms-asf',
            'atom'    => 'application/atom+xml',
            'avi'     => 'video/x-msvideo',
            'bmp'     => 'image/bmp',
            'bz2'     => 'application/x-bzip2',
            'cer'     => 'application/pkix-cert',
            'crl'     => 'application/pkix-crl',
            'crt'     => 'application/x-x509-ca-cert',
            'css'     => 'text/css',
            'csv'     => 'text/csv',
            'cu'      => 'application/cu-seeme',
            'deb'     => 'application/x-debian-package',
            'doc'     => 'application/msword',
            'docx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dvi'     => 'application/x-dvi',
            'eot'     => 'application/vnd.ms-fontobject',
            'eps'     => 'application/postscript',
            'epub'    => 'application/epub+zip',
            'etx'     => 'text/x-setext',
            'flac'    => 'audio/flac',
            'flv'     => 'video/x-flv',
            'gif'     => 'image/gif',
            'gz'      => 'application/gzip',
            'htm'     => 'text/html',
            'html'    => 'text/html',
            'ico'     => 'image/x-icon',
            'ics'     => 'text/calendar',
            'ini'     => 'text/plain',
            'iso'     => 'application/x-iso9660-image',
            'jar'     => 'application/java-archive',
            'jpe'     => 'image/jpeg',
            'jpeg'    => 'image/jpeg',
            'jpg'     => 'image/jpeg',
            'js'      => 'text/javascript',
            'json'    => 'application/json',
            'latex'   => 'application/x-latex',
            'log'     => 'text/plain',
            'm4a'     => 'audio/mp4',
            'm4v'     => 'video/mp4',
            'mid'     => 'audio/midi',
            'midi'    => 'audio/midi',
            'mov'     => 'video/quicktime',
            'mkv'     => 'video/x-matroska',
            'mp3'     => 'audio/mpeg',
            'mp4'     => 'video/mp4',
            'mp4a'    => 'audio/mp4',
            'mp4v'    => 'video/mp4',
            'mpe'     => 'video/mpeg',
            'mpeg'    => 'video/mpeg',
            'mpg'     => 'video/mpeg',
            'mpg4'    => 'video/mp4',
            'oga'     => 'audio/ogg',
            'ogg'     => 'audio/ogg',
            'ogv'     => 'video/ogg',
            'ogx'     => 'application/ogg',
            'pbm'     => 'image/x-portable-bitmap',
            'pdf'     => 'application/pdf',
            'pgm'     => 'image/x-portable-graymap',
            'png'     => 'image/png',
            'pnm'     => 'image/x-portable-anymap',
            'ppm'     => 'image/x-portable-pixmap',
            'ppt'     => 'application/vnd.ms-powerpoint',
            'pptx'    => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ps'      => 'application/postscript',
            'qt'      => 'video/quicktime',
            'rar'     => 'application/x-rar-compressed',
            'ras'     => 'image/x-cmu-raster',
            'rss'     => 'application/rss+xml',
            'rtf'     => 'application/rtf',
            'sgm'     => 'text/sgml',
            'sgml'    => 'text/sgml',
            'svg'     => 'image/svg+xml',
            'swf'     => 'application/x-shockwave-flash',
            'tar'     => 'application/x-tar',
            'tif'     => 'image/tiff',
            'tiff'    => 'image/tiff',
            'torrent' => 'application/x-bittorrent',
            'ttf'     => 'application/x-font-ttf',
            'txt'     => 'text/plain',
            'wav'     => 'audio/x-wav',
            'webm'    => 'video/webm',
            'wma'     => 'audio/x-ms-wma',
            'wmv'     => 'video/x-ms-wmv',
            'woff'    => 'application/x-font-woff',
            'wsdl'    => 'application/wsdl+xml',
            'xbm'     => 'image/x-xbitmap',
            'xls'     => 'application/vnd.ms-excel',
            'xlsx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml'     => 'application/xml',
            'xpm'     => 'image/x-xpixmap',
            'xwd'     => 'image/x-xwindowdump',
            'yaml'    => 'text/yaml',
            'yml'     => 'text/yaml',
            'zip'     => 'application/zip',
            'php'     => 'text/html',
            'svgz'    => 'image/svg+xml',
            'exe'     => 'application/x-msdownload',
            'msi'     => 'application/x-msdownload',
            'cab'     => 'application/vnd.ms-cab-compressed',
            'odt'     => 'application/vnd.oasis.opendocument.text',
            'ods'     => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        [, $ext] = \explode('.', $filename);

        if (\array_key_exists(\strtolower($ext), $mime_types)) {
            return $mime_types[$ext];
        } else {
            return 'application/octet-stream';
        }
    }
}

if (!\function_exists('hyper')) {
	\define('SYMPLELY_USER_AGENT', 'Symplely Hyper PHP/' . \PHP_VERSION);

    // Content types for header data.
	\define('TYPE_HTML', BodyInterface::HTML_TYPE);
	\define('TYPE_OCTET', BodyInterface::OCTET_TYPE);
	\define('TYPE_XML', BodyInterface::XML_TYPE);
	\define('TYPE_PLAIN', BodyInterface::PLAIN_TYPE);
	\define('TYPE_MULTI', BodyInterface::MULTI_TYPE);
	\define('TYPE_JSON', BodyInterface::JSON_TYPE);
    \define('TYPE_FORM', BodyInterface::FORM_TYPE);

	\define('BAD_CALL', "Invalid call on null, no `request` or `response` instance found!");
	\define('BAD_ACCESS', "Invalid access, only HTTP `task` id allowed!");
	\define('BAD_ID', "'Invalid HTTP task ID!'");

	/**
     * Helper function, shouldn't be called directly.
     *
	 * - This function otherwise needs to be prefixed with `yield`
	 */
    function hyper(): array
    {
        $args = \func_get_args();
        $isRequest = \array_shift($args);
        if (\is_string($isRequest)) {
            $tag = $isRequest;
            $isRequest = \array_shift($args);
            if (!empty($args))
                 $isRequest = \array_shift($args);
        } else {
            $tag = null;
        }

        $http = \http_instance($tag);
        if ($isRequest instanceof RequestInterface) {
            $httpFunction = \awaitAble([$http, 'sendRequest'], $isRequest);
        } elseif ($isRequest instanceof \Generator) {
            global $__uriTag__;
            $httpFunction = $isRequest;
            if (isset($__uriTag__[$tag])
                && $__uriTag__[$tag] instanceof HyperInterface
            ) {
                $http = $__uriTag__[$tag];
            }
        } elseif (\is_array($isRequest)) {
            $method = \array_shift($isRequest);
            $url = \array_shift($isRequest);
            $data = \array_shift($isRequest);
            $httpFunction = \http_function($method, $url, $data, $isRequest);
        }

        return [$httpFunction, $http];
    }

	/**
     * This function works similar to `gatherOptions()`.
	 * Controls how the `fetch()` function operates.
     * `fetch()` will behave like **Promise** functions `All`, `Some`, `Any` in JavaScript.
	 *
	 * @param int $count - Will wait for count to complete, `0` (default) All.
	 * @param bool $exception - If `true` (default), immediately propagated
     * to the task that `yield`ed on wait(). Other awaitables will continue to run.
	 * - If `false`, exceptions are treated the same as successful response results,
     * and aggregated in the response list.
     * @param bool $clearAborted - If `true` (default), close/cancel/abort remaining result/responses
     *
	 * @throws \LengthException - If the number of HTTP tasks less than the desired $count.
	 */
    function fetchOptions(int $count = 0, bool $exception = true, bool $clearAborted = true)
    {
        Hyper::waitOptions($count, $exception, $clearAborted);
    }

	/**
     * This function works similar to `gather()`.
     * Takes an array of request HTTP task id's.
     * Will pause current task and continue other tasks until
     * the supplied request HTTP task id's resolve to an response instance.
     *
     * @return array<ResponseInterface>
     * @throws \Exception - if not an HTTP task id
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function fetch(...$requests)
	{
        return Hyper::wait(...$requests);
    }

	/**
     * This function works similar to `await()`
     * Will resolve to an Response instance when `fetch()`
     * Will immediately return an `int`, and continue to the next instruction.
     *
     * @param ...$request either
     *
     * @param string
     * @param RequestInterface
     * @param Generator
     * @param array
     * - `$method`, `$url`, `$data`, `$authorizeHeaderOptions`
     *
     * @return int HTTP task id
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function request()
	{
        $requests = \func_get_args();
        [$httpFunction, $http] = \hyper(...$requests);
        return Hyper::awaitable($httpFunction, $http);
    }

	/**
     * This function works similar to `cancel_task()`.
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function request_abort(int $httpId)
	{
		return Hyper::cancel($httpId);
    }


	/**
     * Helper function, shouldn't be called directly.
     *
	 * - This function otherwise needs to be prefixed with `yield`
	 */
	function http_function(string $method, string $url, $data = [], ...$authorizeHeaderOptions): \Generator
	{
        switch (\strtoupper($method)) {
            case Request::METHOD_PUT:
                $httpFunction = \http_put($url, $data, $authorizeHeaderOptions);
                break;
            case Request::METHOD_POST:
                $httpFunction = \http_post($url, $data, $authorizeHeaderOptions);
                break;
            case Request::METHOD_GET:
                $httpFunction = \http_get($url, $data);
                break;
            case Request::METHOD_PATCH:
                $httpFunction = \http_patch($url, $data, $authorizeHeaderOptions);
                break;
            case Request::METHOD_HEAD:
                $httpFunction = \http_head($url, $data);
                break;
            case Request::METHOD_OPTIONS:
                $httpFunction = \http_options($url, $data);
                break;
            case Request::METHOD_DELETE:
                $httpFunction = \http_delete($url, $data, $authorizeHeaderOptions);
                break;
        }

        return $httpFunction;
    }

	/**
     * Creates an `Hyper` instance for global HTTP functions by.
	 */
	function http_instance(string $tag = null): HyperInterface
	{
        global $__uri__, $__uriTag__;

        if (empty($tag)) {
            $__uri__ = new Hyper;
        } elseif (!isset($__uriTag__[$tag]) || !$__uriTag__[$tag] instanceof HyperInterface) {
            $__uriTag__[$tag] = new Hyper;
        }

		return empty($tag) ? $__uri__ : $__uriTag__[$tag];
	}

	/**
     * Clear & Close `Hyper`, and `StreamInterface` Instances by.
	 */
	function http_clear($tag = null)
	{
        global $__uri__, $__uriTag__;

        if ($tag instanceof HyperInterface) {
            [, $stream] = $tag->getHyper();
            if ($stream instanceof StreamInterface)
                $stream->close();
        } elseif (empty($tag)) {
            if ($__uri__ instanceof HyperInterface) {
                [, $stream] = $__uri__->getHyper();
                if ($stream instanceof StreamInterface)
                    $stream->close();
            }

            $__uri__ = null;
            unset($GLOBALS['__uri__']);
        } else {
            if (isset($__uriTag__[$tag]) && $__uriTag__[$tag] instanceof HyperInterface) {
                [, $stream] = $__uriTag__[$tag]->getHyper();
                if ($stream instanceof StreamInterface)
                    $stream->close();
            }

            $__uriTag__[$tag] = null;
            unset($GLOBALS['__uriTag__'][$tag]);
        }
    }

	/**
     * Clear & Close `ALL` - `Hyper`, and `StreamInterface` Instances by.
	 */
	function http_clear_all()
	{
        global $__uri__, $__uriTag__;

        if ($__uri__ instanceof HyperInterface) {
            [, $stream] = $__uri__->getHyper();
            if ($stream instanceof StreamInterface)
                $stream->close();
        }
        unset($GLOBALS['__uri__']);

        if (\is_array($__uriTag__)) {
            $uriTags = \array_keys($__uriTag__);
            foreach($uriTags as $key) {
                if ($__uriTag__[$key] instanceof HyperInterface) {
                    [, $stream] = $__uriTag__[$key]->getHyper();
                    if ($stream instanceof StreamInterface)
                        $stream->close();
                }

                $__uriTag__[$key] = null;
                unset($GLOBALS['__uriTag__'][$key]);
            }
        }
	}

	/**
     * Make a GET request, will pause current task, and
     * continue other tasks until an response is received.
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function http_get(string $tagUri = null, ...$authorizeHeaderOptions)
	{
        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $authorizeHeaderOptions);
        if (isset($instance) && $instance instanceof HyperInterface) {
            return yield \response_set(yield $instance->get($url, $option), $tag);
        }

        return false;
	}

	/**
     * Make a PUT request, will pause current task, and
     * continue other tasks until an response is received.
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function http_put(string $tagUri = null, ...$authorizeHeaderOptions)
	{
        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $authorizeHeaderOptions);
        if (isset($instance) && $instance instanceof HyperInterface) {
            $data = \array_shift($option);
            return yield \response_set(yield $instance->put($url, $data, $option), $tag);
        }

        return false;
	}

	/**
     * Make a DELETE request, will pause current task, and
     * continue other tasks until an response is received.
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function http_delete(string $tagUri = null, ...$authorizeHeaderOptions)
	{
        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $authorizeHeaderOptions);
        if (isset($instance) && $instance instanceof HyperInterface) {
            $data = \array_shift($option);
            return yield \response_set(yield $instance->delete($url, $data, $option), $tag);
        }

        return false;
	}

	/**
     * Make a POST request, will pause current task, and
     * continue other tasks until an response is received.
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function http_post(string $tagUri = null, ...$authorizeHeaderOptions)
	{
        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $authorizeHeaderOptions);
        if (isset($instance) && $instance instanceof HyperInterface) {
            $data = \array_shift($option);
            return yield \response_set(yield $instance->post($url, $data, $option), $tag);
        }

        return false;
	}

	/**
     * Make a PATCH request, will pause current task, and
     * continue other tasks until an response is received.
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function http_patch(string $tagUri = null, ...$authorizeHeaderOptions)
	{
        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $authorizeHeaderOptions);
        if (isset($instance) && $instance instanceof HyperInterface) {
            $data = \array_shift($option);
            return yield \response_set(yield $instance->patch($url, $data, $option), $tag);
        }

        return false;
	}

	/**
     * Make a OPTIONS request, will pause current task, and
     * continue other tasks until an response is received.
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function http_options(string $tagUri = null, ...$authorizeHeaderOptions)
	{
        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $authorizeHeaderOptions);
        if (isset($instance) && $instance instanceof HyperInterface) {
            return yield \response_set(yield $instance->options($url, $option), $tag);
        }

        return false;
	}

	/**
     * Make a HEAD request, will pause current task, and
     * continue other tasks until an response is received.
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function http_head(string $tagUri = null, ...$authorizeHeaderOptions)
	{
        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $authorizeHeaderOptions);
        if (isset($instance) && $instance instanceof HyperInterface) {
            return yield \response_set(yield $instance->head($url, $option), $tag);
        }

        return false;
    }

	/**
     * Helper function, shouldn't be called directly.
	 */
    function createTagAndSplit($tag, $authorizeHeaderOptions = [])
    {
		if (empty($tag))
            return false;

        $instance = null;
        if (\strpos($tag, '://') !== false) {
            $url = $tag;
            $tag = null;
            $instance = \http_instance($tag);
        } elseif (!empty($authorizeHeaderOptions)) {
            $url = \array_shift($authorizeHeaderOptions);
            $instance = \http_instance($tag);
        } else {
            return null;
        }

        return [$tag, $url, $instance, $authorizeHeaderOptions];
    }

	/**
     * Placeholder for future use.
	 */
	function response()
	{
    }

    /**
     * Set global functions response instance by.
     *
     * @param \ResponseInterface|mixed $tag
     *
     * @return \ResponseInterface
     */
	function response_set($response, string $tag = null)
	{
        if ($response instanceof ResponseInterface) {
            global $__uriResponse__, $__uriResponseTag__;

            if (empty($tag)) {
                $__uriResponse__ = $response;
            } else {
                $__uriResponseTag__[$tag] = $response;
            }
        }

        return $response;
    }

    /**
     * Clear global functions response instance by.
     *
     * @param \ResponseInterface|mixed $tag
     */
	function response_clear($tag = null)
	{
        global $__uriResponse__, $__uriResponseTag__;

        if ($tag instanceof ResponseInterface) {
            $tag->getBody()->close();
        } elseif (empty($tag)) {
            if ($__uriResponse__ instanceof ResponseInterface)
                $__uriResponse__->getBody()->close();

            $__uriResponse__ = null;
            unset($GLOBALS['__uriResponse__']);
        } elseif (isset($__uriResponseTag__[$tag])){
            if ($__uriResponseTag__[$tag] instanceof ResponseInterface)
                $__uriResponseTag__[$tag]->getBody()->close();

            $__uriResponseTag__[$tag] = null;
            unset($GLOBALS['__uriResponseTag__'][$tag]);
        }
    }

    /**
     * Clear `ALL` global functions response instance by.
     *
     * @param \ResponseInterface|mixed $tag
     */
	function response_clear_all()
	{
        global $__uriResponse__, $__uriResponseTag__;

        if ($__uriResponse__ instanceof ResponseInterface)
            $__uriResponse__->getBody()->close();

        $__uriResponse__ = null;
        unset($GLOBALS['__uriResponse__']);

        if (\is_array($__uriResponseTag__)) {
            $uriResponseTags = \array_keys($__uriResponseTag__);
            foreach($uriResponseTags as $key) {
                if ($__uriResponseTag__[$key] instanceof ResponseInterface)
                    $__uriResponseTag__[$key]->getBody()->close();

                $__uriResponseTag__[$key] = null;
                unset($GLOBALS['__uriResponseTag__'][$key]);
            }
        }
    }

    /**
     * Return current global functions response instance by.
     *
     *  `yield` on `NULL`, not ready yet.
     *
     * @param \ResponseInterface|mixed $tag
     *
     * @return  \ResponseInterface|null
     * @throws \Exception - if no response instance set
     */
	function response_instance($tag = null)
	{
        if ($tag instanceof ResponseInterface)
            return $tag;

        global $__uriResponse__, $__uriResponseTag__, $__uri__, $__uriTag__;

        if (empty($tag)) {
            $request = $__uri__;
            $response = $__uriResponse__;
        } else {
            if (isset($__uriTag__[$tag]))
                $request = $__uriTag__[$tag];
            if (isset($__uriResponseTag__[$tag]))
                $response = $__uriResponseTag__[$tag];
        }

        if (!isset($response) || !$response instanceof ResponseInterface) {
            if (!isset($request) || !$request instanceof HyperInterface)
                \panic(\BAD_CALL);

            return null; // Not ready, yield on null.
        }

        return $response;
    }

    /**
     * Response is a successful one.
     *
     *  `yield` on `NULL`, not ready yet.
     *
     * @param \ResponseInterface|mixed $tag
     *
     * @return bool|null
     * @throws \Exception - if no response instance set
     */
	function response_ok($tag = null): ?bool
	{
        if (($response = \response_instance($tag)) === null)
            return null; // Not ready, yield on null.

        return ($response->getStatusCode() < 400);
    }

    /**
     * Response reason phrase.
     *
     *  `yield` on `NULL`, not ready yet.
     *
     * @param \ResponseInterface|mixed $tag
     *
     * @return string|null
     * @throws \Exception - if no response instance set
     */
	function response_phrase($tag = null): ?string
	{
        if (($response = \response_instance($tag)) === null)
            return null; // Not ready, yield on null.

        return $response->getReasonPhrase();
    }

    /**
     * Response status code.
     *
     *  `yield` on `NULL`, not ready yet.
     *
     * @param \ResponseInterface|mixed $tag
     *
     * @return int|null
     * @throws \Exception - if no response instance set
     */
	function response_code($tag = null): ?int
	{
        if (($response = \response_instance($tag)) === null)
            return null; // Not ready, yield on null.

        return $response->getStatusCode();
    }

    /**
     * Check if response has header key by.
     *
     *  `yield` on `NULL`, not ready yet.
     *
     * @param \ResponseInterface|mixed $tag
     * @param string $header
     *
     * @return bool|null
     * @throws \Exception - if no response instance set
     */
	function response_has($tag = null, string $header): ?bool
	{
        if (($response = \response_instance($tag)) === null)
            return null; // Not ready, yield on null.

        return $response->hasHeader($header);
    }

    /**
     * Retrieve a response value for header key by.
     *
     *  `yield` on `NULL`, not ready yet.
     *
     * @param \ResponseInterface|mixed $tag
     * @param string $header
     *
     * @return string|null
     * @throws \Exception - if no response instance set
     */
	function response_header($tag = null, string $header): ?string
	{
        if (($response = \response_instance($tag)) === null)
            return null; // Not ready, yield on null.

        return $response->getHeaderLine($header);
    }

    /**
     * Response body.
     *
	 * - This function needs to be prefixed with `yield`
     *
     * @param \ResponseInterface|mixed $tag
     *
     * @return mixed
     * @throws \Exception - if no response instance set
	 */
	function response_body($tag = null)
	{
        if (($response = \response_instance($tag)) === null)
            \panic(\BAD_CALL);

        return $response->getBody()->getContents();
    }

    /**
     * Response body been read completely.
     *
     *  `yield` on `NULL`, not ready yet.
     *
     * @param \ResponseInterface|mixed $tag
     *
     * @return bool
     * @throws \Exception - if no response instance set
	 */
	function response_eof($tag = null)
	{
        if (($response = \response_instance($tag)) === null)
            return null; // Not ready, yield on null.

        return $response->getBody()->eof();
    }

    /**
     * Response STREAM body.
     *
	 * - This function needs to be prefixed with `yield`
     *
     * @param \ResponseInterface|mixed $tag
     * @param int $size
     *
     * @return mixed
     * @throws \Exception - if no response instance set
	 */
	function response_stream($tag = null, $size = 8192)
	{
        if (($response = \response_instance($tag)) === null)
            \panic(\BAD_CALL);

        return $response->getBody()->read($size);
    }

    /**
     * Response JSON body.
     *
	 * - This function needs to be prefixed with `yield`
     *
     * @param $tag
     * @param bool|null $assoc
     *
     * @return \stdClass|array|bool
     */
    function response_json($tag = null, bool $assoc = false)
    {
        if (($response = \response_instance($tag)) === null)
            \panic(\BAD_CALL);

        return \json_decode(yield $response->getBody()->getContents(), $assoc);
    }

    /**
     * Response XML body.
     *
	 * - This function needs to be prefixed with `yield`
     *
     * @param $tag
     * @param bool|null $assoc
     *
     * @return \SimpleXMLElement|array|bool
     */
    function response_xml($tag = null, bool $assoc = null)
    {
        if (($response = \response_instance($tag)) === null)
            \panic(\BAD_CALL);

        $data = \simplexml_load_string(yield $response->getBody()->getContents());

        return $assoc === true
            ? \json_decode(\json_encode($data), true) // cruel
            : $data;
    }
}
