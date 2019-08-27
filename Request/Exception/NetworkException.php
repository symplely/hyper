<?php

declare(strict_types=1);

namespace Async\Request\Exception;

use Exception;
use Async\Request\Exception\ClientException;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @codeCoverageIgnore
 */
class NetworkException extends ClientException implements NetworkExceptionInterface{

	/**
	 * @var \Psr\Http\Message\RequestInterface
	 */
	private $request;

	/**
	 * @param string $message
	 * @param \Psr\Http\Message\RequestInterface $request
	 * @param \Exception|null $previous
	 */
    public function __construct(string $message, RequestInterface $request, Exception $previous = null)
    {
		$this->request = $request;

		parent::__construct($message, 0, $previous);
	}

	/**
	 * Returns the request.
	 *
	 * The request object MAY be a different object from the one passed to ClientInterface::sendRequest()
	 *
	 * @return \Psr\Http\Message\RequestInterface
	 */
    public function getRequest():RequestInterface
    {
		return $this->request;
	}

}
