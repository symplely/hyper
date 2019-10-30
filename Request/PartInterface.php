<?php

namespace Async\Request;

interface PartInterface
{
    /**
     * Get the content disposition for multi part.
     *
     * @param string $boundary
     * @param string $name
     */
    public function getMultiPart(string $boundary, string $name);
}
