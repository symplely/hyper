<?php

declare(strict_types=1);

namespace Async\Request;

use Async\Request\AsyncStream;
use Psr\Http\Message\StreamInterface;

/**
 * Trait Message
 *
 * @package Async\Http\MessageTrait
 */
trait MessageTrait
{
    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        $header = $this->getHeader($name);

        if( empty($header) ){
            return "";
        }

        return "{$name}: " . implode(",", $header);
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        if ($this->body === null) {
            return new AsyncStream('');
        }

        return clone $this->body;
    }

    /**
     * Filters body content to make sure it's valid.
     *
     * @param StreamInterface|resource|string $body
     *
     * @return StreamInterface
     */
    protected function filterBody($body): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            return clone $body;
        }

        return new AsyncStream($body);
    }
}

