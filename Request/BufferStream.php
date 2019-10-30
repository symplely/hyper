<?php

declare(strict_types=1);

namespace Async\Request;

use Psr\Http\Message\StreamInterface;

class BufferStream implements StreamInterface
{
    /**
     * The buffer contents.
     *
     * @var string
     */
    protected $buffer = "";

    protected $usingBuffer = true;

    /**
     * BufferStream constructor.
     *
     * @param string $data
     */
    public function __construct($data = "")
    {
        $this->buffer = $data;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->getContents();
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        $this->buffer = "";
        return;
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        return $this->close();
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return \strlen($this->buffer);
    }

    /**
     * @inheritDoc
     */
    public function tell()
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function eof()
    {
        return (\strlen($this->buffer) === 0);
    }

    /**
     * @inheritDoc
     */
    public function isSeekable()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException("A BufferStream is not seekable.");
    }

    /**
     * @inheritDoc
     * @return bool
     */
    public function rewind()
    {
        return $this->seek(0);
    }

    /**
     * @inheritDoc
     */
    public function isWritable()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function write($string)
    {
        $this->buffer .= $string;
        return \strlen($string);
    }

    /**
     * @inheritDoc
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function read($length)
    {
        if ($length >= \strlen($this->buffer)) {
            return $this->getContents();
        }

        $chunk = \substr($this->buffer, 0, $length);
        $this->buffer = \substr($this->buffer, $length);
        return $chunk;
    }

    /**
     * @inheritDoc
     */
    public function getContents()
    {
        $buffer = $this->buffer;
        $this->buffer = "";
        return $buffer;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key = null)
    {
        return null;
    }
}
