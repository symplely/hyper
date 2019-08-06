<?php

declare(strict_types = 1);

use Async\Request\BodyInterface;
use Async\Request\Hyper;
use Async\Request\HyperInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;

if(!\function_exists('mime_content_type')) {

    function mime_content_type($filename)
    {
        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );
        [, $ext] = \explode('.', $filename);
        if (\array_key_exists(\strtolower($ext), $mime_types)) {
            return $mime_types[$ext];
        } else {
            return 'application/octet-stream';
        }
    }
}

if (!\function_exists('create_uri')) {
	\define('SYMPLELY_USER_AGENT', 'Symplely Http PHP/' . \PHP_VERSION);

    // Content types for header data.
	\define('TYPE_HTML', BodyInterface::HTML_TYPE);
	\define('TYPE_OCTET', BodyInterface::OCTET_TYPE);
	\define('TYPE_XML', BodyInterface::XML_TYPE);
	\define('TYPE_PLAIN', BodyInterface::PLAIN_TYPE);
	\define('TYPE_MULTI', BodyInterface::MULTI_TYPE);
	\define('TYPE_JSON', BodyInterface::JSON_TYPE);
	\define('TYPE_FORM', BodyInterface::FORM_TYPE);

	function create_uri(string $tag = null): HyperInterface
	{
		global $__uri__, $__uriTag__;

        if (empty($tag)) {
            if (!$__uri__ instanceof HyperInterface)
                $__uri__ = new Hyper;
        } elseif (!isset($__uriTag__[$tag]) || !$__uriTag__[$tag] instanceof HyperInterface) {
            $__uriTag__[$tag] = new Hyper;
        }

		return empty($tag) ? $__uri__ : $__uriTag__[$tag];
	}

	function clear_uri(string $tag = null)
	{
        global $__uri__, $__uriTag__;

        if (empty($tag)) {
            if ($__uri__ instanceof HyperInterface)
                $__uri__->close();

            $__uri__ = null;
            unset($GLOBALS['__uri__']);
        } else {
            if (isset($__uriTag__[$tag]) && $__uriTag__[$tag] instanceof HyperInterface)
                $__uriTag__[$tag]->close();

            $__uriTag__[$tag] = null;
            unset($GLOBALS['__uriTag__'][$tag]);
        }
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function get_uri(string $tagUri = null, ...$options)
	{
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function put_uri(string $tagUri = null, ...$options)
	{
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function delete_uri(string $tagUri = null, ...$options)
	{
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function post_uri(string $tagUri = null, ...$options)
	{
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function patch_uri(string $tagUri = null, ...$options)
	{
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function head_uri(string $tagUri = null, ...$options)
	{
		if (empty($tagUri))
            return false;

        [$url, $instance, $options] = \tagOptionsSplit($tagUri, $options);

        if (isset($instance) && $instance instanceof HyperInterface) {
            $response = yield $instance->head($url, $options);

            return $response;
        }

        return false;
    }
    
    function tagOptionsSplit($tag, $options)
    {
        $instance = null;
        if (\strpos($tag, '://') !== false) {
            $instance = \create_uri();
        } elseif (!empty($options)) {
            $tag = \array_shift($options);
            $instance = \create_uri($tag);
        }

        return [$tag, $instance, $options];
    }

    /**
     * @param ResponseInterface $response
     * @param bool|null $assoc
     *
     * @return \stdClass|array|bool
     */
    function get_json(ResponseInterface $response, bool $assoc = null)
    {
        return \json_decode($response->getBody()->getContents(), $assoc);
    }

    /**
     * @param ResponseInterface $response
     * @param bool|null $assoc
     *
     * @return \SimpleXMLElement|array|bool
     */
    function get_xml(ResponseInterface $response, bool $assoc = null)
    {
        $data = \simplexml_load_string($response->getBody()->getContents());

        return $assoc === true
            ? \json_decode(\json_encode($data), true) // cruel
            : $data;
    }

    /**
     * Returns the string representation of an HTTP message.
     *
     * @param MessageInterface $message Message to convert to a string.
     *
     * @return string
     */
    function message_to_string(MessageInterface $message): string
    {
        $msg = '';

        if ($message instanceof RequestInterface) {
            $msg = \trim($message->getMethod().' '.$message->getRequestTarget()).' HTTP/'.$message->getProtocolVersion();

            if (!$message->hasHeader('host')) {
                $msg .= "\r\nHost: ".$message->getUri()->getHost();
            }

        } elseif ($message instanceof ResponseInterface) {
            $msg = 'HTTP/'.$message->getProtocolVersion().' '.$message->getStatusCode().' '.$message->getReasonPhrase();
        }

        foreach ($message->getHeaders() as $name => $values) {
            $msg .= "\r\n".$name.': '.\implode(', ', $values);
        }

        return $msg."\r\n\r\n".$message->getBody();
    }

    /**
     * Decompresses the message content according to the Content-Encoding header and returns the decompressed data
     *
     * @param MessageInterface $message
     *
     * @return string
     */
    function decompress_content(MessageInterface $message): string
    {
        $data = $message->getBody()->getContents();

        switch($message->getHeaderLine('content-encoding')) {
            case 'compress':
                return \gzuncompress($data);
            case 'deflate' : 
                return \gzinflate($data);
            case 'gzip'    : 
                return \gzdecode($data);
            default: 
                return $data;
        }
    }
}
