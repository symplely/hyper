<?php

declare(strict_types=1);

namespace Async\Tests;

use Async\Request\Request;
use Async\Request\BufferStream;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function test_valid_protocol_versions_allowed()
    {
        $request = new Request;
        $request = $request->withProtocolVersion("2.0");
        $this->assertEquals("2.0", $request->getProtocolVersion());
    }

    public function test_protocol_version_not_allowed()
    {
        $this->expectException(\Exception::class);

        $request = new Request;
        $request = $request->withProtocolVersion("2.1");
    }

    public function test_with_protocol_version_is_imuutable()
    {
        $request = new Request;
        $newRequest = $request->withProtocolVersion("2.0");
        $this->assertNotEquals($request, $newRequest);
    }

    public function test_with_body_saves_data()
    {
        $request = new Request;
        $request = $request->withBody(new BufferStream("test body"));
        $this->assertNotEmpty($request->getBody());
    }

    public function test_with_body_is_immutable()
    {
        $request = new Request;
        $newRequest = $request->withBody(new BufferStream("test body"));
        $this->assertNotEquals($request, $newRequest);
    }

    public function test_get_header_returns_array()
    {
        $request = (new Request)->withHeader("Content-Type", "application/json");
        $this->assertTrue(is_array($request->getHeader("Content-Type")));
    }

    public function test_get_header_returns_null_if_header_not_found()
    {
        $request = new Request;
        $header = $request->getHeader("X-Foo");

        $this->assertEmpty($header);
    }

    public function test_get_header_line_returns_empty_string_if_header_not_found()
    {
        $request = new Request;

        $this->assertEquals("", $request->getHeaderLine("X-Foo"));
    }

    public function test_get_headers_returns_all_headrs()
    {
        $request = new Request;

        $request = $request->withHeader("X-Foo", "FooHeader");
        $request = $request->withHeader("X-Bar", "BarHeader");

        $this->assertEquals([
            "X-Foo" => ["FooHeader"],
            "X-Bar" => ["BarHeader"]
        ], $request->getHeaders());
    }

    public function test_with_added_header_uses_header_name_as_is_if_not_found()
    {
        $request = new Request;

        $request = $request->withAddedHeader("X-Foo", "FooHeader");

        $this->assertEquals("X-Foo: FooHeader", $request->getHeaderLine("X-Foo"));
    }

    public function test_with_header_replaces_existing_header()
    {
        $request = (new Request)->withHeader("Content-Type", "application/json");
        $request = $request->withHeader("Content-Type", "text/html");
        $this->assertEquals("Content-Type: text/html", $request->getHeaderLine("Content-Type"));
    }

    public function test_with_added_header_adds_new_value()
    {
        $request = (new Request)->withHeader("X-Foo", "bar");
        $request = $request->withAddedHeader("X-Foo", "baz");

        $this->assertEquals(2, count($request->getHeader("X-Foo")));
    }

    public function test_header_names_are_case_insensitive()
    {
        $request = (new Request)->withHeader("X-Foo", "bar");
        $this->assertNotEmpty($request->getHeader("x-foo"));
    }

    public function test_without_header_removes_header()
    {
        $request = (new Request)->withHeader("X-Foo", "bar");
        $request = $request->withoutHeader("X-Foo");

        $this->assertFalse($request->hasHeader("X-Foo"));
    }

    public function test_without_header_returns_same_instance_if_header_not_found()
    {
        $request = new Request;

        $newRequest = $request->withoutHeader("X-Foo");

        $this->assertEquals($request, $newRequest);
    }
}