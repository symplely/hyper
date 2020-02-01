<?php

declare(strict_types=1);

namespace Async\Request;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\Exceptions\RuntimeException;
use Async\Coroutine\Exceptions\InvalidArgumentException;
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
    private $resource;

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

    /**
     * The streams associated `Task` id, if any.
     *
     * @var int
     */
    private $hyperId;

    /**
     * Does stream has support for gzip and `inflate/deflate` content encoding.
     *
     * @var bool
     */
    private $hasZlib;

    /**
     * @var resource
     */
    private $contextInflate;

    /**
     * @var resource
     */
    private $contextDeflate;

    /**
     * @var int
     */
    private $zlibEncoding;

    /**
     * @var resource[]
     */
    private static $nonBlocking = [];

    /**
     * @param resource $stream
     * @param string $zlib gzip stream with inflate or deflate, if available.
     *
     * @throws InvalidArgumentException If a resource or string isn't given.
     */
    public function __construct(
        $stream = null,
        ?string $zlib = null,
        int $encoding = 0,
        int $level = 1
    ) {
        if ($zlib == 'inflate') {
            $this->inflate($encoding);
        } elseif ($zlib == 'deflate') {
            $this->deflate($encoding, $level);
        }

        if ($stream == null) {
            $stream = @\fopen('php://temp', 'rb+');
            \rewind($stream);
        } elseif (!\is_resource($stream) || 'stream' !== \get_resource_type($stream)) {
            throw new InvalidArgumentException(
                'Invalid stream provided; must be a string stream identifier or stream resource'
            );
        }

        self::setNonBlocking($stream);
        $this->resource = $stream;
        $meta = $this->getMetadata();
        $this->uri = $meta['uri'] ?? null;
        $this->seekable = $meta['seekable'];
        $this->writable = $this->isWritable();
        $this->readable = $this->isReadable();
    }

    public function withTask(?int $hyperId)
    {
        $this->hyperId = $hyperId;

        return $this;
    }

    /**
     * Initialize an incremental `inflate` context.
     *
     * @param int $encoding compression algorithm used, see `inflate_init()`
     *
     * @see http://php.net/manual/en/function.inflate-init.php
     */
    public function inflate(int $encoding = 0)
    {
        if (\function_exists('inflate_init') && ($encoding > 0)) {
            $this->hasZlib = true;
            $this->zlibEncoding = $encoding;
            $this->contextInflate = @\inflate_init($this->zlibEncoding);
        }

        return $this;
    }

    /**
     * Check for support for gzip and `inflate` content.
     */
    public function isInflate(): bool
    {
        return $this->hasZlib && \is_resource($this->contextInflate);
    }

    /**
     * Initialize an incremental `deflate` context.
     *
     * @param int $encoding compression algorithm used, see `deflate_init()`
     * @param int $level compression level to use.
     *
     * @see http://php.net/manual/en/function.deflate-init.php
     */
    public function deflate(int $encoding = 0, int $level = 1)
    {
        if (\function_exists('deflate_init')) {
            $this->hasZlib = true;
            $this->zlibEncoding = ($encoding == 0) ? \ZLIB_ENCODING_RAW : $encoding;
            $this->contextDeflate = @\deflate_init($this->zlibEncoding, ['level' => $level]);
        }

        return $this;
    }

    /**
     * Check for support for gzip and `deflate` content.
     */
    public function isDeflate(): bool
    {
        return $this->hasZlib && \is_resource($this->contextDeflate);
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
            throw new RuntimeException('Stream is not open.');
        }

        $position = \ftell($handle);
        if ($position === false) {
            throw new RuntimeException('Unable to get position of stream.');
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
            throw new RuntimeException('Stream is not open.');
        }

        if (!\rewind($handle)) {
            throw new RuntimeException('Failed to rewind stream.');
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
            throw new RuntimeException('Stream is not open.');
        }

        if (0 > \fseek($handle, $offset, $whence)) {
            throw new RuntimeException(
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
        } catch (\Throwable $e) {
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $handle = $this->detach();;

        if (\is_resource($handle)) {
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
        $this->hyperId = null;
        $this->contextInflate = null;
        $this->contextDeflate = null;
        $this->zlibEncoding = null;
        $this->hasZlib = false;
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
        yield;
        $handle =  $this->getResource();
        if ($this->isReadable() && ($handle !== null)) {
            $buffer = "";
            $start = \microtime(true);
            while (true) {
                $begin = \microtime(true);
                yield Kernel::readWait($handle, true);
                if ($this->isInflate()) {
                    $new = self::inflateRead($this, $handle);
                } else {
                    $new = \fread($handle, \FETCH_CHUNK);
                }

                $end = \microtime(true);
                if (\is_string($new) && \strlen($new) >= 1) {
                    $buffer .= $new;
                }

                $time_used = $end - $begin;
                if (($time_used >= 0.25) || !\is_string($new) || (\is_string($new) && \strlen($new) < 1)) {
                    if ($this->isInflate()) {
                        $data = @\inflate_add($this->contextInflate, '', \ZLIB_FINISH);
                        if ($data !== false)
                            $buffer .= $data;
                        $this->contextInflate = null;
                    }

                    break;
                }
            }

            $timer = \microtime(true) - $start;
            if (false !== $buffer) {
                yield \log_notice(
                    'On task: {httpId} {class}, {url} Received: {transferred} bytes Took: {timer}ms',
                    ['httpId' => $this->hyperId, 'class' => __METHOD__, 'url' => $this->uri, 'transferred' => \strlen($buffer), 'timer' => $timer],
                    \hyper_loggerName()
                );

                yield Coroutine::value($buffer);
            } else {
                yield \log_critical('Unable to get contents from underlying resource', \hyper_loggerName());
                throw new RuntimeException('Unable to get contents from underlying resource');
            }
        } else {
            yield \log_critical('Underlying resource is not readable', \hyper_loggerName());
            throw new RuntimeException('Underlying resource is not readable');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        $handle =  $this->getResource();
        if (!$this->isReadable() || ($handle === null)) {
            yield \log_critical('Stream is not readable', \hyper_loggerName());
            throw new RuntimeException('Stream is not readable');
        }

        if ($length < 0) {
            yield \log_critical('Length parameter cannot be negative', \hyper_loggerName());
            throw new RuntimeException('Length parameter cannot be negative');
        }

        if ($length === 0) {
            yield Coroutine::value('');
        } else {
            $start = \microtime(true);
            yield Kernel::readWait($handle, true);
            if ($this->isInflate()) {
                $contents = self::inflateRead($this, $handle);
            } else {
                $contents = \fread($handle, $length);
            }

            $timer = \microtime(true) - $start;
            if ($this->isInflate()) {
                $data = @\inflate_add($this->contextInflate, '', \ZLIB_FINISH);
                if ($data !== false)
                    $contents .= $data;
                $this->contextInflate = null;
            }

            if (false !== $contents) {
                yield \log_notice(
                    'On task: {httpId} {class}, {url} Read: {read} bytes Took: {timer}ms',
                    ['httpId' => $this->hyperId, 'class' => __METHOD__, 'url' => $this->uri, 'read' => \strlen($contents), 'timer' => $timer],
                    \hyper_loggerName()
                );

                yield Coroutine::value($contents);
            } else {
                yield \log_critical('Unable to read from underlying resource', \hyper_loggerName());
                throw new RuntimeException('Unable to read from underlying resource');
            }
        }
    }

    /**
     * Binary-safe file read
     *
     * @param AsyncStream $stream
     * @param resource $handle
     * @param integer|null $length
     * @return string
     */
    protected static function inflateRead(AsyncStream $stream, $handle)
    {
        if (false !== ($data = \fread($handle, 8192))) {
            $chunk = @\inflate_add($stream->contextInflate, $data, \ZLIB_SYNC_FLUSH);
            if ($chunk !== '') {
                if (\strlen($data) < 8192) {
                    $chunk .= @\inflate_add($stream->contextInflate, '', \ZLIB_FINISH);
                    $stream->contextInflate = null;
                }

                return $chunk;
            }
        }

        if (\strlen($data) <= 8192) {
            $data .= @\inflate_add($stream->contextInflate, '', \ZLIB_FINISH);
            $stream->contextInflate = null;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        $handle =  $this->getResource();
        if (!$this->isWritable() || ($handle === null)) {
            yield \log_critical('Stream is not writable', \hyper_loggerName());
            throw new RuntimeException('Stream is not writable');
        }

        // We can't know the size after writing anything
        $this->size = null;

        $start = \microtime(true);
        yield Kernel::writeWait($handle, true);
        if ($this->isDeflate()) {
            $written = self::deflateWrite($this, $handle, $string);
        } else {
            $written = \fwrite($handle, $string);
        }

        $timer = \microtime(true) - $start;
        if (false !== $written) {
            yield \log_notice(
                'On task: {httpId} {class}, Response: {url} Written: {written} bytes Took: {timer}ms',
                ['httpId' => $this->hyperId, 'class' => __METHOD__, 'url' => $this->uri, 'written' => $written, 'timer' => $timer],
                \hyper_loggerName()
            );

            yield Coroutine::value($written);
        } else {
            yield \log_critical('Unable to write to underlying resource', \hyper_loggerName());
            throw new RuntimeException('Unable to write to underlying resource');
        }
    }

    /**
     * Binary-safe file write
     *
     * @param AsyncStream $stream
     * @param resource $handle
     * @param mixed $chunk
     * @return int
     */
    protected static function deflateWrite(AsyncStream $stream, $handle, $chunk)
    {
        $written = 0;
        $chunk = \deflate_add($stream->contextDeflate, $chunk, \ZLIB_FULL_FLUSH);
        if ($chunk !== '') {
            $written = \fwrite($handle, $chunk);
            $stream->contextDeflate = null;
        }

        return $written;
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
     * @param string|null $zlib gzip stream with either inflate or deflate, if available.
     *
     * @return AsyncStream
     * @throws InvalidArgumentException for invalid streams or resources.
     */
    public static function create(
        string $content = '',
        ?string $zlib = null,
        int $encoding = 0,
        int $level = 1
    ) {
        $resource = @\fopen('php://temp', 'rb+');
        $stream = new self($resource, $zlib, $encoding, $level);
        yield $stream->write($content);
        $stream->rewind();
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
     * @throws RuntimeException If the file cannot be opened.
     * @throws InvalidArgumentException If the mode is invalid.
     */
    public static function createFromFile(string $filename, string $mode = 'r'): AsyncStream
    {
        return new self(\fopen($filename, $mode));
    }

    /**
     * Create a compress stream from an existing file.
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
     * @throws RuntimeException If the file cannot be opened.
     * @throws InvalidArgumentException If the mode is invalid.
     */
    public static function createDeflateFromFile(string $filename, string $mode = 'r')
    {
        $stream = \fopen($filename, $mode);
        $instance = new self($stream);
        $contents = yield $instance->getContents();
        $instance->close();
        $instance = null;

        $instance = yield self::create($contents, 'deflate');
        return $instance;
    }

    /**
     * Create a new stream from an existing resource.
     *
     * The stream MUST be readable and may be writable.
     *
     * @param resource $resource PHP resource to use as basis of stream.
     *
     * @return AsyncStream
     */
    public static function createFromResource($resource): AsyncStream
    {
        return new self($resource);
    }

    /**
     * @param StreamInterface|resource $source
     * @param StreamInterface|resource|null $destination
     *
     * @return AsyncStream
     * @throws InvalidArgumentException for not an resource.
     * @throws RuntimeException for unable to write to underlying resource.
     */
    public static function copyResource($source, $destination = null)
    {
        $source = $source instanceof AsyncStream ? $source->getResource() : $source;
        $destination = $destination instanceof AsyncStream ? $destination->getResource() : $destination;
        if (!\is_resource($source)) {
            throw new InvalidArgumentException('Not resource.');
        }

        if (\stream_get_meta_data($source)['seekable']) {
            \rewind($source);
        }

        if (empty($destination)) {
            $destination = \fopen('php://temp', 'rb+');
        }

        self::setNonBlocking($source);
        if (!\is_resource($destination)) {
            throw new InvalidArgumentException('Not resource.');
        }

        self::setNonBlocking($destination);
        while (!\feof($source)) {
            yield Kernel::readWait($source, true);
            $data = \stream_get_contents($source, \FETCH_CHUNK);
            $count = \strlen($data);
            if ($count) {
                yield Kernel::writeWait($destination, true);
                $result = \fwrite($destination, $data);
                if (false === $result) {
                    throw new RuntimeException('Unable to write to underlying resource');
                }
            }
        };

        $stream = new self($destination);
        $stream->rewind();

        return $stream;
    }

    /**
     * Returns a pair of connected domain stream socket resources.
     *
     * @return resource[] Pair of non-blocking socket resources.
     *
     * @throws RuntimeException If creating the sockets fails.
     */
    public static function pair(): array
    {
        $domain = (\DIRECTORY_SEPARATOR == '\\') ? \STREAM_PF_INET : \STREAM_PF_UNIX;

        if (false === ($sockets = \stream_socket_pair($domain, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP))) {
            $message = 'Failed to create socket pair.';
            if ($error = \error_get_last()) {
                $message .= \sprintf(' Errno: %d; %s', $error['type'], $error['message']);
            }

            throw new RuntimeException($message);
        }

        return [self::setNonBlocking($sockets[0]), self::setNonBlocking($sockets[1])];
    }

    public static function setNonBlocking($socket)
    {
        self::$nonBlocking[(int) $socket] = true;
        if (!\stream_set_blocking($socket, false)) {
            self::$nonBlocking[(int) $socket] = false;
        }

        \stream_set_read_buffer($socket, 0);
        \stream_set_write_buffer($socket, 0);

        return $socket;
    }
}
