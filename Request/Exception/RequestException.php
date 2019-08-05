<?php

declare(strict_types=1);

namespace Async\Request\Exception;

use Throwable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Async\Request\Exception\ClientException;

/**
 * @codeCoverageIgnore
 */
class RequestException extends ClientException implements RequestExceptionInterface
{
	/**
	 * @var RequestInterface
	 */
	private $request;

    /**
     * RequestException constructor.
     *
     * @param RequestInterface $request
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(RequestInterface $request, $message, $code, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }

	/**
	 * Returns the request.
	 *
	 * The request object MAY be a different object from the one passed to ClientInterface::sendRequest()
	 *
	 * @return RequestInterface
	 */
	public function getRequest(): RequestInterface
	{
		return $this->request;
	}

}
