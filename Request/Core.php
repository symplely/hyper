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

if (!\function_exists('create_uri')) {
	\define('SYMPLELY_USER_AGENT', 'Symplely Hyper PHP/' . \PHP_VERSION);

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
		if (empty($tagUri))
            return false;

        [$url, $instance, $options] = \tagOptionsSplit($tagUri, $options);

        if (isset($instance) && $instance instanceof HyperInterface) {
            $response = yield $instance->get($url, $options);

            return $response;
        }

        return false;
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
