<?php

declare(strict_types=1);

namespace Async\Request;

use Psr\Http\Message\UriInterface;
/**
 * Class Uri
 *
 * @package Async\Request\Uri
 */
class Uri extends \Async\Http\Uri
{
    /**
     * Create a new URI.
     *
     * @param string $uri
     *
     * @return UriInterface
     * @throws \InvalidArgumentException If the given URI cannot be parsed.
     */
    public static function create(string $uri = ''): UriInterface
    {
        $obj = new self();
        if (empty($uri)) {
            return $obj;
        }

        $url = \parse_url($uri);
        if (!$url) {
            throw new \InvalidArgumentException('URL passed is not a well-formed URI');
        }

        if (isset($url['fragment'])) {
            $obj = $obj->withFragment($url['fragment']);
        }

        if (isset($url['host'])) {
            $obj = $obj->withHost($url['host']);
        }

        if (isset($url['path'])) {
            $obj = $obj->withPath($url['path']);
        }

        if (isset($url['port'])) {
            $obj = $obj->withPort($url['port']);
        }

        if (isset($url['query'])) {
            $obj = $obj->withQuery($url['query']);
        }

        if (isset($url['scheme'])) {
            $obj = $obj->withScheme($url['scheme']);
        }

        if (isset($url['user'])) {
            $password = isset($url['pass']) ? $url['pass'] : null;
            $obj = $obj->withUserInfo($url['user'], $password);
        }

        return $obj;
    }
}
