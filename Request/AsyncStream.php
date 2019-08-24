<?php

declare(strict_types=1);

namespace Async\Request;

use Async\Coroutine\Kernel;
use Psr\Http\Message\StreamInterface;

/**
 * Class AsyncStream
 *
 * @package Async\Request\AsyncStream
 */
class AsyncStream implements StreamInterface
{
    /**
     * @var string[]
    */
    private const WRITABLE_MODES = ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'];

    /**
     * @var string[]
     */
    private const READABLE_MODES = ['r', 'r+', 'w+', 'a+', 'x+', 'c+'];

    /**
     * Stream of data.
     *
     * @var resource|null
     */
    protected $resource;

	/**
	 * @var bool
	 */
	private $seekable;

	/**
	 * @var bool
	 */
	private $readable;

	/**
	 * @var bool
	 */
    private $writable;

	/**
	 * @var string|null
	 */
	private $uri;

	/**
	 * @var int|null
	 */
    private $size;

    protected static $nonBlocking = [];

    /**
     * @param resource|string $stream
     *
     * @throws \InvalidArgumentException If a resource or string isn't given.
     */
    public function __construct($stream = null)
    {
        if (\is_resource($stream)) {
            self::setNonBlocking($stream);
            $this->resource = $stream;
        } elseif (\is_string($stream)) {
            $this->setStream($stream);
        } elseif (!\is_resource($stream) || 'stream' !== \get_resource_type($stream)) {
            throw new \InvalidArgumentException(
                'Invalid stream provided; must be a string stream identifier or stream resource'
            );
        }

		$meta = $this->getMetadata();

		$this->uri      = $meta['uri'] ?? null;
		$this->seekable = $meta['seekable'];
		$this->writable = $this->isWritable();
		$this->readable = $this->isReadable();
    }

    /**
     * Set the internal stream resource.
     *
     * @param string|resource $stream String stream target or stream resource.
     * @throws \InvalidArgumentException for invalid streams or resources.
     */
    protected function setStream($stream)
    {
        $resource = @\fopen('php://temp', 'rb+');
        if (\is_resource($resource)) {
            self::setNonBlocking($resource);
            Kernel::writeWait($resource);
            \fwrite($resource, $stream);
            \rewind($resource);
        } else {
            throw new \InvalidArgumentException(
                'Invalid stream provided; must be a string stream identifier or stream resource'
            );
        }

        $this->resource = $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize(): ?int
    {
        $handle = $this->getResource();

        if ($handle === null) {
            return null;
        }

        if ($this->size !== null) {
            return $this->size;
        }

        // Clear the stat cache if the stream has a URI
        if ($this->uri) {
            \clearstatcache(true, $this->uri);
        }

        $stats = \fstat($handle);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function tell(): int
    {
        $handle = $this->getResource();

        if ($handle === null) {
            throw new \RuntimeException('Stream is not open.');
        }

        $position = \ftell($handle);
        if ($position === false) {
            throw new \RuntimeException('Unable to get position of stream.');
        }

        return $position;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $handle = $this->getResource();

        if ($handle === null) {
            throw new \RuntimeException('Stream is not open.');
        }

        if (!\rewind($handle)) {
            throw new \RuntimeException('Failed to rewind stream.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isSeekable(): bool
    {
        $handle = $this->getResource();

        if ($handle === null) {
            return false;
        }

        $seekable = $this->getMetadata('seekable');
        if ($seekable === null) {
            return false;
        }

        return $seekable;
    }

    /**
     * {@inheritDoc}
     */
    public function seek($offset, $whence = \SEEK_SET): void
    {
        $handle = $this->getResource();

        if ($handle === null) {
            throw new \RuntimeException('Stream is not open.');
        }

        if (0 > \fseek($handle, $offset, $whence)) {
            throw new \RuntimeException(
                \sprintf('Failed to seek to offset %s.', $offset)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
		if ($this->getResource() === null) {
			return '';
        }

        try {
			if ($this->seekable) {
				$this->seek(0);
            }

            return $this->getContents();
        } catch (\Exception $e) {
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $handle = $this->detach();;

		if(\is_resource($handle)) {
			\fclose($handle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource = $this->resource;

        $this->resource = null;
        $this->size = null;
        $this->uri = null;
		$this->readable = false;
		$this->writable = false;
		$this->seekable = false;
        self::$nonBlocking = [];

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        $handle = $this->getResource();

        if (isset($handle)) {
            return \feof($handle);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        $handle = $this->getResource();

        if ($this->readable && ($handle !== null)) {
			yield Kernel::readWait($handle);
			$buffer = \stream_get_contents($handle);

            if (false !== $buffer) {
                return $buffer;
            }

            throw new \RuntimeException('Unable to get contents from underlying resource');
        }

        throw new \RuntimeException('Underlying resource is not readable');
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        $handle = $this->getResource();

        if ($handle === null) {
            return null;
        }

        $metadata = \stream_get_meta_data($handle);
        if ($key) {
            $metadata = isset($metadata[$key]) ? $metadata[$key] : null;
        }

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        $handle = $this->getResource();

        if ($handle === null) {
            return false;
        }

        $mode = $this->getMetadata('mode');
        if ($mode === null) {
            return false;
        }

        $mode = \str_replace(['b', 'e'], '', $mode);
        return \in_array($mode, self::READABLE_MODES, true);
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        $handle = $this->getResource();

        if (!$this->readable || ($handle === null)) {
            throw new \RuntimeException('Stream is not readable');
        }

		if($length < 0) {
			throw new \RuntimeException('Length parameter cannot be negative');
        }

		if($length === 0){
			return '';
        }

        yield Kernel::readWait($handle);
        $contents = \fread($handle, $length);

        if (false !== $contents) {
            return $contents;
        }

        throw new \RuntimeException('Unable to read from underlying resource');
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($stream = null)
    {
        $handle = $this->getResource();

        if ($handle === null) {
            return false;
        }

        $mode = $this->getMetadata('mode');
        if ($mode === null) {
            return false;
        }

        $mode = \str_replace(['b', 'e'], '', $mode);
        return \in_array($mode, self::WRITABLE_MODES, true);
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        $handle = $this->getResource();

        if (!$this->writable || ($handle === null)) {
            throw new \RuntimeException('Stream is not writable');
        }

		// We can't know the size after writing anything
        $this->size = null;

        yield Kernel::writeWait($handle);
        $result = \fwrite($handle, $string);
        if (false !== $result) {
            return $result;
        }

        throw new \RuntimeException('Unable to write to underlying resource');
    }

    public function getResource()
    {
        if (\is_resource($this->resource)) {
            return $this->resource;
        }

        return null;
    }

    /**
     * Create a new stream from a string.
     *
     * The stream SHOULD be created with a temporary resource.
     *
     * @param string $content String content with which to populate the stream.
     *
     * @return StreamInterface
     */
    public static function create(string $content = '')
    {
        $stream = new self($content);
        yield Kernel::writeWait($stream->resource);
        \fwrite($stream->resource, $content);
        \rewind($stream->resource);
        return $stream;
    }

    /**
     * Create a stream from an existing file.
     *
     * The file MUST be opened using the given mode, which may be any mode
     * supported by the `fopen` function.
     *
     * The `$filename` MAY be any string supported by `fopen()`.
     *
     * @param string $filename Filename or stream URI to use as basis of stream.
     * @param string $mode Mode with which to open the underlying filename/stream.
     *
     * @return StreamInterface
     * @throws \RuntimeException If the file cannot be opened.
     * @throws \InvalidArgumentException If the mode is invalid.
     */
    public static function createFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $stream = \fopen($filename, $mode);
        return new self($stream);
    }

    /**
     * Create a new stream from an existing resource.
     *
     * The stream MUST be readable and may be writable.
     *
     * @param resource $resource PHP resource to use as basis of stream.
     *
     * @return StreamInterface
     */
    public static function createFromResource($resource): StreamInterface
    {
        return new self($resource);
    }

	/**
	 * @param resource $resource
     * @param resource|null $copy
	 *
	 * @return StreamInterface
     * @throws \InvalidArgumentException for not an resource.
     * @throws \RuntimeException for unable to write to underlying resource.
	 */
	public static function copyResource($resource, $copy = null)
	{
		if (!\is_resource($resource)) {
			throw new \InvalidArgumentException('Not resource.');
		}

		if (\stream_get_meta_data($resource)['seekable']) {
			\rewind($resource);
		}

		if (empty($copy)) {
			$copy = \fopen('php://temp', 'rb+');
		}

		self::setNonBlocking($resource);
		if (!\is_resource($copy)) {
			throw new \InvalidArgumentException('Not resource.');
		}

		self::setNonBlocking($copy);
        while (!\feof($resource)) {
			yield Kernel::readWait($resource);
			$data = \stream_get_contents($resource, 512);
            $count = \strlen($data);
            if ($count) {
				yield Kernel::writeWait($copy);
				$result = \fwrite($copy, $data);
				if (false === $result) {
					throw new \RuntimeException('Unable to write to underlying resource');
				}
            }
        };

		$stream = new self($copy);
		$stream->rewind();

		return $stream;
	}

	/**
     * @param resource $source
     * @param resource $destination
	 *
	 * @return StreamInterface
     * @throws \InvalidArgumentException for not an resource.
     * @throws \RuntimeException for unable to write to underlying resource.
	 */
    public static function pipe($source, $destination): StreamInterface
    {
        return self::copyResource($source, $destination);
    }

    /**
     * Uses PHP's zlib.inflate filter to inflate deflate or gzipped content.
     *
     * This stream decorator skips the first 10 bytes of the given stream to remove
     * the gzip header, converts the provided stream to a PHP stream resource,
     * then appends the zlib.inflate filter. The stream is then converted back
     * to a Guzzle stream resource to be used as a Guzzle stream.
     *
     * @link http://tools.ietf.org/html/rfc1952
     * @link http://php.net/manual/en/filters.compression.php
     *
	 * @param StreamInterface $stream
	 *
	 * @return StreamInterface
     */
	public static function inflate(StreamInterface $stream)
	{
		$stream->rewind();

		yield $stream->read(10);

		$resource = \fopen('php://temp', 'rb+');

		while (!$stream->eof()) {
			$data = yield $stream->read(1048576);
			yield Kernel::writeWait($resource);
			\fwrite($resource, $data);
		}

		\fseek($resource, 0);

		\stream_filter_append($resource, "zlib.inflate", \STREAM_FILTER_READ);

		return self::copyResource($resource);
	}

    /**
     * Returns a pair of connected domain stream socket resources.
     *
     * @return resource[] Pair of non-blocking socket resources.
     *
     * @throws \RuntimeException If creating the sockets fails.
     */
    public static function pair(): array
    {
        $domain = (\DIRECTORY_SEPARATOR == '\\') ? \STREAM_PF_INET : \STREAM_PF_UNIX;

        if (false === ($sockets = \stream_socket_pair($domain, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP))) {
            $message = 'Failed to create socket pair.';
            if ($error = \error_get_last()) {
                $message .= \sprintf(' Errno: %d; %s', $error['type'], $error['message']);
            }

            throw new \RuntimeException($message);
        }

        return [self::setNonBlocking($sockets[0]), self::setNonBlocking($sockets[1])];
    }

	public static function setNonBlocking($socket)
    {
		self::$nonBlocking[(int)$socket] = true;
        if (!\stream_set_blocking($socket, false)) {
			self::$nonBlocking[(int)$socket] = false;
        }

        \stream_set_read_buffer($socket, 0);
        \stream_set_write_buffer($socket, 0);

        return $socket;
    }
}
