<?php

declare(strict_types=1);

namespace Async\Request;

use Async\Request\PartInterface;

interface BodyInterface
{
    // Content types for header data.
    const HTML_TYPE = 'text/html';
	const OCTET_TYPE = 'application/octet-stream';
	const XML_TYPE = 'application/xml';
	const PLAIN_TYPE = 'text/plain';
	const MULTI_TYPE = 'multipart/form-data';
	const JSON_TYPE = 'application/json';
	const FORM_TYPE = 'application/x-www-form-urlencoded';

    // Body construct types for body part.
	const XML = '_xml_';
	const JSON = '_json_';
	const FORM = '_form_';
	const FILE = '_file_';
    const MULTI = '_multi_';

    /**
     * Xml Body constructor.
     *
     * @param string $data
     * @param string|null $contentType
     *
     * @return BodyInterface
     */
    public function xml(string $data = "", string $contentType = null);

	/**
     * Json Body constructor.
     *
     * @param array $data
     * @param string|null $contentType
     */
    public function json(array $data, string $contentType = null);

    /**
     * Form Body constructor.
     *
     * @param array $data
     * @param string|null $contentType
     */
    public function form(array $data, string $contentType = null);

	/**
     * File Body constructor
     *
     * @param StreamInterface|string $file StreamInterface instance of file or the full path of file to open.
     * @param string|null $fileName Filename to assign to content.
     * @param string|null $contentType File mime content type.
     */
    public function file($file, ?string $fileName = null, string $contentType = null);

    /**
     * MultiPart Body constructor.
     *
     * @param array<string, PartInterface> $parts
     */
    public function multi(array $parts, string $contentType = null);

    /**
     * Get the boundary string.
     *
     * @return string
     */
    public function getBoundary();

    /**
     * Get the body's Content-Type header value.
     *
     * @return string
     */
    public function getContentType();
}