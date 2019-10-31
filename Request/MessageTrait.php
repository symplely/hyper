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
     * The requested options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Debug mode flag.
     *
     * @var bool
     */
    protected $debug = false;

    protected $httpId = null;

    public function taskPid(?int $httpId)
    {
        $this->httpId = $httpId;

        return $this;
    }

    public function withOptions(array $options = [])
    {
        $message = clone $this;
        $message->options = \array_merge($message->options, $options);

        return $message;
    }

    public function getOptions(): array
    {
        return $this->options;
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

    public function debugging(): bool
    {
        return $this->debug;
    }

    /**
     * Enable debug mode for the message.
     *
     * Debug mode will print additional connection, request, and response information to STDOUT.
     *
     * @param boolean $debug
     * @return self
     */
    public function debugOn(): self
    {
        $this->debug = true;

        return $this;
    }

    /**
     * Disable debug mode for the message.
     *
     * Debug mode will print additional connection, request, and response information to STDOUT.
     *
     * @param boolean $debug
     * @return self
     */
    public function debugOff(): self
    {
        $this->debug = false;

        return $this;
    }

    /**
     * Debug request and response.
     *
     * @param int $notification_code
     * @param int $severity
     * @param string $message
     * @param int $message_code
     * @param int $bytes_transferred
     * @param int $bytes_max
     * @return void
     */
    public function debug($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max): void
    {
        switch ($notification_code) {
            case STREAM_NOTIFY_CONNECT:
                $debug = "Connected";
                break;
            case STREAM_NOTIFY_RESOLVE:
                $debug = "Resolve: {$message}";
                break;
            case STREAM_NOTIFY_AUTH_REQUIRED:
                $debug = "Auth required: {$message}";
                break;
            case STREAM_NOTIFY_COMPLETED:
                $debug = "Completed: {$message}";
                break;
            case STREAM_NOTIFY_FAILURE:
                $debug = "Failure: {$message}";
                break;
            case STREAM_NOTIFY_AUTH_RESULT:
                $debug = "Auth result: {$message}";
                break;
            case STREAM_NOTIFY_REDIRECTED:
                $debug = "Redirect: {$message}";
                break;
            case STREAM_NOTIFY_FILE_SIZE_IS:
                $debug = "Content size: {$bytes_max}";
                break;
            case STREAM_NOTIFY_MIME_TYPE_IS:
                $debug = "Content mime-type: {$message}";
                break;
            case STREAM_NOTIFY_PROGRESS:
                $debug = "Transferred: {$bytes_transferred}";
                break;
            default:
                $debug = "Unknown";
        }

        $preamble = \json_encode([
            "notification_code" => $notification_code,
            "severity" => $severity,
            "message" => $message,
            "message_code" => $message_code,
            "bytes_transferred" => $bytes_transferred,
            "bytes_max" => $bytes_max,
        ]);

        print "{$preamble}\n{$debug}\n";
    }
}
