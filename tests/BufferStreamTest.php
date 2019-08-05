<?php

declare(strict_types=1);

namespace Async\Tests;

use Async\Request\BufferStream;
use PHPUnit\Framework\TestCase;

class BufferStreamTest extends TestCase
{
    public function test_contructor_applies_data()
    {
        $bufferStream = new BufferStream("Symplely!");
        $this->assertEquals("Symplely!", $bufferStream->getContents());
    }

    public function test_casting_to_string_returns_contents()
    {
        $bufferStream = new BufferStream("Symplely!");
        $this->assertEquals("Symplely!", (string) $bufferStream);
    }

    public function test_close_resets_buffer_contents()
    {
        $bufferStream = new BufferStream("Symplely!");
        $bufferStream->close();
        $this->assertEquals("", $bufferStream->getContents());
    }

    public function test_detach_resets_buffer_contents()
    {
        $bufferStream = new BufferStream("Symplely!");
        $bufferStream->detach();
        $this->assertEquals("", $bufferStream->getContents());
    }

    public function test_getsize_returns_string_length_of_buffer()
    {
        $bufferStream = new BufferStream("Symplely!");
        $this->assertEquals(9, $bufferStream->getSize());
    }

    public function test_tell_of_bufferstream_is_always_zero()
    {
        $bufferStream = new BufferStream("Symplely!");
        $this->assertEquals(0, $bufferStream->tell());
    }

    public function test_eof_when_buffer_is_empty()
    {
        $bufferStream = new BufferStream;
        $this->assertTrue($bufferStream->eof());
    }

    public function test_is_not_seekable()
    {
        $bufferStream = new BufferStream;
        $this->assertTrue(!$bufferStream->isSeekable());
    }

    public function test_seek_throws_exception()
    {
        $this->expectException(\Exception::class);
        $bufferStream = new BufferStream("Symplely!");
        $bufferStream->seek(0);
    }

    public function test_rewind_throws_exception()
    {
        $bufferStream = new BufferStream("Symplely!");
        $this->expectException(\Exception::class);
        $bufferStream->rewind();
    }

    public function test_is_writeable()
    {
        $bufferStream = new BufferStream;

        $this->assertTrue($bufferStream->isWritable());
    }

    public function test_write_returns_bytes_written()
    {
        $bufferStream = new BufferStream;
        $bytesWritten = $bufferStream->write("Symplely!");

        $this->assertEquals(9, $bytesWritten);
    }

    public function test_write_appends_data()
    {
        $bufferStream = new BufferStream("I love");
        $bufferStream->write(" Symplely!");

        $this->assertEquals("I love Symplely!", $bufferStream->getContents());
    }

    public function test_is_readable()
    {
        $bufferStream = new BufferStream;

        $this->assertTrue($bufferStream->isReadable());
    }

    public function test_reading_more_bytes_than_available()
    {
        $bufferStream = new BufferStream("Symplely!");
        $data = $bufferStream->read(25);

        $this->assertEquals("Symplely!", $data);
    }

    public function test_reading_fewer_bytes_than_available()
    {
        $bufferStream = new BufferStream("Symplely!");
        $data = $bufferStream->read(2);

        $this->assertEquals("Sy", $data);
    }

    public function test_reading_bytes_removes_from_stream()
    {
        $bufferStream = new BufferStream("Symplely!");
        $bufferStream->read(2);
        $data = $bufferStream->getContents();

        $this->assertEquals("mplely!", $data);
    }

    public function test_get_contents_returns_entire_buffer()
    {
        $bufferStream = new BufferStream("Symplely!");
        $data = $bufferStream->getContents();
        $this->assertEquals("Symplely!", $data);
    }

    public function test_get_contents_empties_buffer()
    {
        $bufferStream = new BufferStream("Symplely!");
        $bufferStream->getContents();

        $this->assertEquals("", $bufferStream->getContents());
        $this->assertTrue($bufferStream->eof());
    }

    public function test_get_meta_data_returns_nothing()
    {
        $bufferStream = new BufferStream("Symplely!");
        $this->assertEquals(null, $bufferStream->getMetadata());
    }
}