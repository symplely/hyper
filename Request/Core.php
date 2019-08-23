<?php

declare(strict_types = 1);

use Async\Request\Hyper;
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

	\define('BAD_CALL', "Invalid access/call on null, no `request` or `response` instance found!");

    function hyper(callable $awaitableFunction, ...$args)
    {
        return yield yield $awaitableFunction(...$args);
    }

	/**
     * This function works similar to `gatherOptions()`.
	 * Controls how the `fetch()` function operates.
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
     * Takes an array of request HTTP task id's, if not `int` covert to request object,
     * using identical parameters as in `Request()`
     *
     * @param ...$request either
     *
     * @param string
     * @param RequestInterface
     * @param Generator
     * @param array
     * - `$method`, `$url`, `$data`, `$options`
     *
     * @return array<ResponseInterface>
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function fetch(...$requests)
	{
        $http = [];
        $httpList = \is_array($requests[0]) ? $requests[0] : $requests;
        foreach($httpList as $request) {
            if (\is_int($request)) {
                $http[$request] = $request;
            } else {
                $id = \request($request);
                $http[$id] = $id;
            }
        }

        return Hyper::wait($http);
    }

	/**
     * This function works similar to `await()`
     * Will resolve to an Response instance when `fetch()`
     *
     * @param ...$request either
     *
     * @param string
     * @param RequestInterface
     * @param Generator
     * @param array
     * - `$method`, `$url`, `$data`, `$options`
     *
     * @return int HTTP task id
     *
	 * - This function needs to be prefixed with `yield`
	 */
	function request()
	{
        $args = \func_get_args();
        $isRequest = \array_shift($args);
        if (\is_string($isRequest)) {
            $tag = $isRequest;
            $isRequest = \array_shift($args);
            if (!empty($args))
                 $isRequest = \array_shift($args);
        } else {
            $tag = 'null';
        }

        $http = \http_instance($tag);
        if ($isRequest instanceof RequestInterface) {
            $httpFunction = $http->sendRequest($isRequest);
        } elseif ($isRequest instanceof \Generator) {
            global $__uri__, $__uriTag__;
            $httpFunction = $isRequest;
            if (($tag !== 'null') && isset($__uriTag__[$tag]) && $__uriTag__[$tag] instanceof HyperInterface) {
                $http = $__uriTag__[$tag];
            } elseif (($tag === 'null') && isset($__uri__) && $__uri__ instanceof HyperInterface) {
                $http = $__uri__;
            }
        } elseif (\is_array($isRequest)) {
            $method = \array_shift($isRequest);
            $url = \array_shift($isRequest);
            $data = \array_shift($isRequest);
            $request = $http->request($method, $url, $data, $isRequest);
            $httpFunction = $http->sendRequest($request);
        }

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

	function http_instance(string $tag = null): HyperInterface
	{
        global $__uri__, $__uriTag__;

        if ($tag === 'null') {
            $__uri__ = new Hyper;
            return $__uri__;
        }

        if (empty($tag)) {
            if (!$__uri__ instanceof HyperInterface)
                $__uri__ = new Hyper;
        } elseif (!isset($__uriTag__[$tag]) || !$__uriTag__[$tag] instanceof HyperInterface) {
            $__uriTag__[$tag] = new Hyper;
        }

		return empty($tag) ? $__uri__ : $__uriTag__[$tag];
	}

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
	 * - This function needs to be prefixed with `yield`
	 */
	function http_get(string $tagUri = null, ...$options)
	{
		if (empty($tagUri))
            return false;

        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $options);
        if (isset($instance) && $instance instanceof HyperInterface) {
            return yield \response_set(yield $instance->get($url, $option), $tag);
        }

        return false;
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function http_put(string $tagUri = null, ...$options)
	{
		if (empty($tagUri))
            return false;

        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $options);
        if (isset($instance) && $instance instanceof HyperInterface) {
            $data = \array_shift($option);
            return yield \response_set(yield $instance->put($url, $data, $option), $tag);
        }

        return false;
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function http_delete(string $tagUri = null, ...$options)
	{
		if (empty($tagUri))
            return false;

        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $options);
        if (isset($instance) && $instance instanceof HyperInterface) {
            $data = \array_shift($options);
            return yield \response_set(yield $instance->delete($url, $data, $option), $tag);
        }

        return false;
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function http_post(string $tagUri = null, ...$options)
	{
		if (empty($tagUri))
            return false;

        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $options);
        if (isset($instance) && $instance instanceof HyperInterface) {
            $data = \array_shift($option);
            return yield \response_set(yield $instance->post($url, $data, $option), $tag);
        }

        return false;
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function http_patch(string $tagUri = null, ...$options)
	{
		if (empty($tagUri))
            return false;

        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $options);
        if (isset($instance) && $instance instanceof HyperInterface) {
            $data = \array_shift($option);
            return yield \response_set(yield $instance->patch($url, $data, $option), $tag);
        }

        return false;
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function http_options(string $tagUri = null, ...$options)
	{
		if (empty($tagUri))
            return false;

        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $options);
        if (isset($instance) && $instance instanceof HyperInterface) {
            return yield \response_set(yield $instance->options($url, $option), $tag);
        }

        return false;
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function http_head(string $tagUri = null, ...$options)
	{
		if (empty($tagUri))
            return false;

        [$tag, $url, $instance, $option] = \createTagAndSplit($tagUri, $options);
        if (isset($instance) && $instance instanceof HyperInterface) {
            return yield \response_set(yield $instance->head($url, $option), $tag);
        }

        return false;
    }

    function createTagAndSplit($tag, $options = [])
    {
        $instance = null;
        if (\strpos($tag, '://') !== false) {
            $url = $tag;
            $tag = 'null';
            $instance = \http_instance($tag);
        } elseif (!empty($options)) {
            $url = \array_shift($options);
            $instance = \http_instance($tag);
        } else {
            return null;
        }

        return [$tag, $url, $instance, $options];
    }

	function response()
	{
    }

	function response_set(ResponseInterface $response, string $tag = null)
	{
        global $__uriResponse__, $__uriResponseTag__;

        if (empty($tag)) {
            $__uriResponse__ = $response;
        } else {
            $__uriResponseTag__[$tag] = $response;
        }

        return $response;
    }

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
     * @return boolean
     */
	function response_ok($tag = null): ?bool
	{
        if (($response = \response_instance($tag)) === null)
            return null; // Not ready, yield on null.

        return ($response->getStatusCode() < 400);
    }

	function response_phrase($tag = null): ?string
	{
        if (($response = \response_instance($tag)) === null)
            return null; // Not ready, yield on null.

        return $response->getReasonPhrase();
    }

	function response_code($tag = null): ?int
	{
        if (($response = \response_instance($tag)) === null)
            return null; // Not ready, yield on null.

        return $response->getStatusCode();
    }

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function response_body($tag = null)
	{
        if (($response = \response_instance($tag)) === null)
            \panic(\BAD_CALL);

        return $response->getBody()->getContents();
    }

    /**
     * @param $tag
     * @param bool|null $assoc
     *
     * @return \stdClass|array|bool
     *
	 * - This function needs to be prefixed with `yield`
     */
    function response_json($tag = null, bool $assoc = false)
    {
        if (($response = \response_instance($tag)) === null)
            \panic(\BAD_CALL);

        return \json_decode(yield $response->getBody()->getContents(), $assoc);
    }

    /**
     * @param $tag
     * @param bool|null $assoc
     *
     * @return \SimpleXMLElement|array|bool
     *
	 * - This function needs to be prefixed with `yield`
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
