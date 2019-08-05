<?php

declare(strict_types=1);

namespace Async\Request;

use Async\Request\AsyncStream;
use Async\Request\BufferStream;
use Async\Request\BodyInterface;
use Async\Request\PartInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @package Async\Request\Body
 */
class Body extends BufferStream implements BodyInterface, PartInterface
{
    /**
     * File contents to upload.
     *
     * @var StreamInterface
     */
    protected $stream;

    /**
     * File name to use in multipart/form-data content disposition.
     *
     * @var string
     */
    protected $fileName;

    /**
     * The file's content type.
     *
     * @var string
     */
    protected $fileContentType;

    /**
     * Content-Type header data.
     *
     * E.g. application/json
     *
     * @var string
     */
    protected $contentType = 'text/plain';

    /**
     * __constructor call type
     *
     * @var string
     */
    protected $type = null;

    /**
     * Boundary string.
     *
     * @var string
     */
    protected $boundary;

    /**
     * @param string $bodyType - defaults as data for `buffer`, and Content-Type `text/plain`
     * @param array|string|null $data
     * @param string|null $content
     * @param string|null $extra
     */
    public function __construct(string $bodyType = '', $data = '', $content = null, $extra = null)
    {
		switch (\strtolower($bodyType)) {
			case self::XML:
				$this->xml($data, $content);
				break;
			case self::JSON:
				$this->json($data, $content);
				break;
			case self::FORM:
				$this->form($data, $content);
				break;
			case self::FILE:
				$this->file($data, $content, $extra);
				break;
			case self::MULTI:
				$this->multi($data, $content);
                break;
            default:
                $this->buffer = $bodyType;
                if (\strpos($data, '/') !== false)
                    $this->contentType = $data;
                break;
		}
    }

    public static function create(string $bodyType = '', $data = '', $content = null, $extra = null)
    {
        return new self($bodyType, $data, $content, $extra);
    }

    /**
     * {@inheritDoc}
     */
    public function xml(string $data = '', string $contentType = null)
    {
        $this->buffer = $data;
        $this->type = self::XML;
        $this->contentType = empty($contentType) ? self::XML_TYPE : $contentType;
    }

    /**
     * @inheritDoc
     */
    public function json(array $data, string $contentType = null)
    {
        if (($json = \json_encode($data)) === false) {
            throw new \Exception('Invalid JSON');
        }

        $this->buffer = $json;
        $this->type = self::JSON;
		$this->contentType = empty($contentType) ? self::JSON_TYPE : $contentType;
    }

    /**
     * @inheritDoc
     */
    public function form(array $data, string $contentType = null)
    {
        $this->buffer = \http_build_query($data, 'n', '&', \PHP_QUERY_RFC1738);
        $this->type = self::FORM;
		$this->contentType = empty($contentType) ? self::FORM_TYPE : $contentType;
    }

    /**
     * @inheritDoc
     */
    public function file($file, ?string $fileName = null, string $contentType = null)
    {
        if (\is_string($file)) {
            $this->usingBuffer = false;
            $this->stream = AsyncStream::createFromFile($file);
            $this->fileName = $fileName ?? \basename($file);
            $this->fileContentType = $contentType ?? \mime_content_type($file);
        } elseif ($file instanceof StreamInterface) {
            $this->usingBuffer = false;
            if ($file instanceof BufferStream)
                $this->usingBuffer = true;
            $this->stream = $file;
            $this->fileName = $fileName ?? \basename($file->getMetadata('uri') ?? 'document');
            $this->fileName = ($this->fileName == 'temp') ? 'document' : $this->fileName;
            $this->fileContentType = $contentType ?? self::PLAIN_TYPE;
        }

        $this->type = self::FILE;
    }

    /**
     * @inheritDoc
     */
    public function multi(array $parts, string $contentType = null)
    {
        // Create a random boundary name for each multipart request.
        $this->boundary = \uniqid('Symplely') . 'Z';
        $this->type = self::MULTI;
		$this->contentType = empty($contentType) ? self::MULTI_TYPE : $contentType;

        /**
         * @var string $name
         * @var PartInterface $part
         */
        foreach($parts as $name => $part) {
            if (!\is_string($name)) {
                throw new \Exception('Please provide a name for each part of a Multipart request.');
            }

            $this->write($part->getMultiPart($this->boundary, $name));
        }

        $this->write("\r\n--{$this->boundary}--\r\n");
    }

    /**
     * @inheritDoc
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

    /**
     * @inheritDoc
     */
    public function getContentType()
    {
		if ($this->contentType == 'multipart/form-data')
			return "{$this->contentType};boundary={$this->boundary}";

        return $this->contentType;
    }

    /**
     * @inheritDoc
     */
    public function getMultiPart(string $boundary, string $name)
    {
        $multipart = '';
		switch ($this->type) {
			case self::FORM:
				return $this->formMultiPart($boundary, $name);
            case self::FILE:
                if ($this->usingBuffer === true)
                    return $this->fileMultiPart($boundary, $name);

                return $this->asyncMultiPart($boundary, $name);
            case self::XML:
			case self::JSON:
			default:
                $multipart .= "\r\n--{$boundary}\r\n";
                $multipart .= "Content-Disposition: form-data; name=\"{$name}\"\r\n";
                $multipart .= "Content-Type: {$this->getContentType()}\r\n\r\n";
                $multipart .= $this->buffer;
				break;
		}

        return $multipart;
    }

	/**
	 * Format a key => value pair array into a Form Urlencoded string.
	 */
    protected function formMultiPart(string $boundary, ?string $name = null): string
    {
        // Convert the form data back into an array
        \parse_str($this->buffer, $formFields);

        $multiPart = '';

        foreach ($formFields as $name => $value) {
            $multiPart .= "\r\n--{$boundary}\r\n";
            $multiPart .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
            $multiPart .= $value;
        }

        return $multiPart;
    }

	/**
	 * Useable only within in a MultpartFormBody.
	 */
    protected function fileMultiPart(string $boundary, string $name)
    {
        // Rewind the stream, just in case we're at the end.
        if ($this->stream->isSeekable()) {
            $this->stream->rewind();
        }

        // Build out multi-part
        $multipart = "\r\n--{$boundary}\r\n";
        $multipart .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$this->fileName}\"\r\n";
        $multipart .= "Content-Type: {$this->fileContentType}\r\n";
        $multipart .= "Content-Length: {$this->stream->getSize()}\r\n\r\n";
        $multipart .= $this->stream->getContents();

        return $multipart;
    }

    protected function asyncMultiPart(string $boundary, string $name)
    {
        // Rewind the stream, just in case we're at the end.
        if ($this->stream->isSeekable()) {
            $this->stream->rewind();
        }

        // Build out multi-part
        $multipart = "\r\n--{$boundary}\r\n";
        $multipart .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$this->fileName}\"\r\n";
        $multipart .= "Content-Type: {$this->fileContentType}\r\n";
        $multipart .= "Content-Length: {$this->stream->getSize()}\r\n\r\n";
        $multipart .=  yield $this->stream->getContents();

        return $multipart;
    }
}