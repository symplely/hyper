<?php

declare(strict_types=1);

namespace Async\Tests;

use Async\Request\Body;
use Async\Request\AsyncStream;
use Async\Request\BodyInterface;
use Async\Request\BufferStream;
use PHPUnit\Framework\TestCase;

class BodyTest extends TestCase
{
    protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function test_setting_body_in_buffer_body_constructor()
    {
        $body = new Body("OK", "text/plain");
        $this->assertEquals("OK", $body->getContents());
    }

    public function test_setting_content_type_in_buffer_body_constructor()
    {
        $body = new Body("OK", "text/plain");
        $this->assertEquals("text/plain", $body->getContentType());
    }

    public function test_get_multipart()
    {
        $body = new Body("OK");

        $this->assertEquals(
            "\r\n--Symplely\r\nContent-Disposition: form-data; name=\"test\"\r\nContent-Type: text/plain\r\n\r\nOK",
            $body->getMultiPart("Symplely", "test")
        );
    }

    public function task_create_instance_from_file_on_disk()
    {
        $fileBody = new Body(Body::FILE, __DIR__ . \DS . 'files' . \DS . 'plainText.txt');

        $this->assertEquals(
            "\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"plainText.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 9\r\n\r\nSymplely!",
            yield $fileBody->getMultiPart("BOUNDARY", "file")
        );
    }

    public function test_create_instance_from_file_on_disk()
    {
        \coroutine_run($this->task_create_instance_from_file_on_disk());
    }

    public function task_create_instance_from_file_on_disk_with_filename_override()
    {
        $fileBody = new Body(Body::FILE, __DIR__ . \DS . 'files' . \DS . 'plainText.txt', "plain.txt");

        $result = yield $fileBody->getMultiPart("BOUNDARY", "file");
        $this->assertEquals(
            "\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"plain.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 9\r\n\r\nSymplely!",
            $result
        );
    }

    public function test_create_instance_from_file_on_disk_with_filename_override()
    {
        \coroutine_run($this->task_create_instance_from_file_on_disk_with_filename_override());
    }

    public function task_create_instance_from_file_on_disk_with_content_type_override()
    {
        $fileBody = new Body(BodyInterface::FILE, __DIR__ . \DS . 'files' . \DS . 'plainText.txt', null, "text/html");

        $this->assertEquals(
            "\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"plainText.txt\"\r\nContent-Type: text/html\r\nContent-Length: 9\r\n\r\nSymplely!",
            yield $fileBody->getMultiPart("BOUNDARY", "file")
        );
    }

    public function test_create_instance_from_file_on_disk_with_content_type_override()
    {
        \coroutine_run($this->task_create_instance_from_file_on_disk_with_content_type_override());
    }

    public function task_create_instance_from_stream()
    {
        $fileBody = new Body(Body::FILE, new AsyncStream('Symplely!'));

        $this->assertEquals(
            "\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"document\"\r\nContent-Type: text/plain\r\nContent-Length: 9\r\n\r\nSymplely!",
            yield $fileBody->getMultiPart("BOUNDARY", "file")
        );
    }

    public function test_create_instance_from_stream()
    {
        \coroutine_run($this->task_create_instance_from_stream());
    }

    public function test_create_instance_from_stream_with_filename_override()
    {
        $fileBody = new Body(Body::FILE, new BufferStream('Symplely!'), 'buffer.txt');

        $this->assertEquals(
            "\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"buffer.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 9\r\n\r\nSymplely!",
            $fileBody->getMultiPart("BOUNDARY", "file")
        );
    }

    public function task_create_instance_from_stream_with_content_type_override()
    {
        $fileBody = new Body(Body::FILE, new AsyncStream('Symplely!'), null, 'text/html');

        $this->assertEquals(
            "\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"document\"\r\nContent-Type: text/html\r\nContent-Length: 9\r\n\r\nSymplely!",
            yield $fileBody->getMultiPart("BOUNDARY", "file")
        );
    }

    public function test_create_instance_from_stream_with_content_type_override()
    {
        \coroutine_run($this->task_create_instance_from_stream_with_content_type_override());
    }

    public function test_form_default_content_type()
    {
        $formBody = Body::create(Body::FORM, []);

        $this->assertEquals("application/x-www-form-urlencoded", $formBody->getContentType());
    }

    public function test_form_override_content_type()
    {
        $formBody = new Body(Body::FORM, [], "text/html");

        $this->assertEquals("text/html", $formBody->getContentType());
    }

    public function test_form_body_transformation()
    {
        $formBody = Body::create(
            Body::FORM,
            [
                "name" => "John Doe",
                "email" => "jdoe@example.com",
            ]
        );

        $this->assertEquals("name=John+Doe&email=jdoe%40example.com", $formBody->getContents());
    }

    public function test_json_default_content_type()
    {
        $jsonBody = Body::create(Body::JSON, []);

        $this->assertEquals("application/json", $jsonBody->getContentType());
    }

    public function test_json_override_content_type()
    {
        $jsonBody = new Body(Body::JSON, [], "application/vnd.api+json");

        $this->assertEquals("application/vnd.api+json", $jsonBody->getContentType());
    }

    public function test_json_encoding()
    {
        $jsonBody = Body::create(
            Body::JSON,
            [
                "name" => "John Doe",
                "email" => "jdoe@example.com",
            ]
        );

        $this->assertEquals('{"name":"John Doe","email":"jdoe@example.com"}', $jsonBody->getContents());
    }

    public function task_multiple_parts()
    {
        $multiBody = new Body(Body::MULTI, [
            'form' => new Body(Body::FORM, [
                'email' => 'user@example.com',
                'name' => 'Example User',
            ]),
            'file' => new Body(
                Body::FILE,
                new BufferStream("Symplely!"),
                'plain.txt',
                'text/plain'
            )
        ]);

        $boundary = "--" . $multiBody->getBoundary();

        $this->assertEquals(
            "\r\n{$boundary}\r\nContent-Disposition: form-data; name=\"email\"\r\n\r\nuser@example.com\r\n{$boundary}\r\nContent-Disposition: form-data; name=\"name\"\r\n\r\nExample User\r\n{$boundary}\r\nContent-Disposition: form-data; name=\"file\"; filename=\"plain.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 9\r\n\r\nSymplely!\r\n{$boundary}--\r\n",
            $multiBody->getContents()
        );
    }

    public function test_multiple_parts_without_yield()
    {
        \coroutine_run($this->task_multiple_parts());
    }

    public function test_multiple_parts_without_key_throws_exception()
    {
        $this->expectException(\Exception::class);

        $multiBody = new Body(Body::MULTI, [
            'form' => new Body(Body::FORM, [
                'email' => 'user@example.com',
                'name' => 'Example User',
            ]),

            new Body(
                Body::FILE,
                new BufferStream("Symplely!"),
                'plain.txt',
                'text/plain'
            )
        ]);
    }

    public function test_multipart_content_type()
    {
        $multiBody = Body::create(Body::MULTI, []);

        $this->assertEquals(
            "multipart/form-data;boundary=" . $multiBody->getBoundary(),
            $multiBody->getContentType()
        );
    }

    public function test_default_content_type()
    {
        $xmlBody = new Body(Body::XML, "");
        $this->assertEquals("application/xml", $xmlBody->getContentType());
    }

    public function test_override_content_type()
    {
        $xmlBody = Body::create(Body::XML, "", "application/xhtml+xml");
        $this->assertEquals("application/xhtml+xml", $xmlBody->getContentType());
    }

    public function test_xml_form_body_transformation()
    {
        $content = <<<XML
<books>
        <book>
            <title>Breakfast Of Champions</title>
            <author>Kurt Vonnegut</author>
        </book>

        <book>
            <title>Time's Arrow</title>
            <author>Martin Amis</title>
        </book>
</books>
XML;

        $xmlBody = Body::create(Body::XML, $content);

        $this->assertEquals($content, $xmlBody->getContents());
    }
}
