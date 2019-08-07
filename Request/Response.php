<?php

declare(strict_types=1);

namespace Async\Request;

use Async\Request\MessageResponse;

/**
 * Class Response
 *
 * @package Async\Request\Response
 */
class Response extends MessageResponse
{
    /**
     * Response is a successful one.
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        return ($this->statusCode < 400);
    }
}
