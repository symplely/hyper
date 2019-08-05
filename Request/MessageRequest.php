<?php

declare(strict_types=1);

namespace Async\Request;

use Async\Request\MessageTrait;

/**
 * Class MessageRequest
 *
 * @package Async\Request\MessageRequest
 */
abstract class MessageRequest extends \Async\Http\Request
{
    use MessageTrait;
}
