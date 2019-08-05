<?php

declare(strict_types=1);

namespace Async\Request;

use Async\Request\MessageTrait;

/**
 * Class MessageResponse
 *
 * @package Async\Request\MessageResponse
 */
abstract class MessageResponse extends Async\Http\Response
{
    use MessageTrait;
}
