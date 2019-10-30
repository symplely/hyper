<?php

declare(strict_types=1);

namespace Async\Request\Exception;

use Psr\Http\Client\ClientExceptionInterface;

class ClientException extends \Exception implements ClientExceptionInterface
{ }
