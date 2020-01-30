<?php

declare(strict_types=1);

namespace Async\Tests;

use Async\Request\Hyper;
use Async\Request\AsyncStream;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;

class AsyncStreamTest extends TestCase
{
    const TARGET_URL = "https://enev6g8on09tl.x.pipedream.net/";
    const TARGET_URLS = "https://httpbin.org/";

    protected function setUp(): void
    {
        \coroutine_clear();
        \hyper_clear();
        $this->http = new Hyper;
    }

    public function testConstructorThrowsExceptionOnInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);

        new AsyncStream(true);
    }

    public function taskConstructorInitializesProperties()
    {
        $stream = yield AsyncStream::create('data');
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
        $this->assertTrue(\is_type($stream->getMetadata(), 'array'));
        $this->assertEquals(4, $stream->getSize());
        $this->assertFalse($stream->eof());
        $stream->close();
    }

    public function testConstructorInitializesProperties()
    {
        \coroutine_run($this->taskConstructorInitializesProperties());
    }

    public function taskConvertsToString()
    {
        $stream = yield AsyncStream::create('data');
        $this->assertEquals('data', yield $stream->__toString());
        $stream->close();
    }

    public function testConvertsToString()
    {
        \coroutine_run($this->taskConvertsToString());
    }

    public function taskGetsContents()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = AsyncStream::createFromResource($handle);
        $this->assertEquals('', yield $stream->getContents());
        $stream->seek(0);
        $this->assertEquals('data', yield $stream->getContents());
        $this->assertEquals('', yield $stream->getContents());
    }

    public function testGetsContents()
    {
        \coroutine_run($this->taskGetsContents());
    }

    public function taskChecksEof()
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = new AsyncStream($handle);
        $this->assertFalse($stream->eof());
        yield $stream->read(4);
        $this->assertTrue($stream->eof());
        $stream->close();
    }

    public function testChecksEof()
    {
        \coroutine_run($this->taskChecksEof());
    }

    public function testGetSize()
    {
        $size = filesize(__FILE__);
        $handle = fopen(__FILE__, 'r');
        $stream = AsyncStream::createFromResource($handle);
        $this->assertEquals($size, $stream->getSize());
        // Load from cache
        $this->assertEquals($size, $stream->getSize());
        $stream->close();
    }

    public function taskEnsuresSizeIsConsistent()
    {
        $h = fopen('php://temp', 'w+');
        $this->assertEquals(3, fwrite($h, 'foo'));
        $stream = AsyncStream::createFromResource($h);
        $this->assertEquals(3, $stream->getSize());
        $this->assertEquals(4, yield $stream->write('test'));
        $this->assertEquals(7, $stream->getSize());
        $this->assertEquals(7, $stream->getSize());
        $stream->close();
    }

    public function testEnsuresSizeIsConsistent()
    {
        \coroutine_run($this->taskEnsuresSizeIsConsistent());
    }

    public function taskProvidesStreamPosition()
    {
        $handle = fopen('php://temp', 'w+');
        $stream = AsyncStream::createFromResource($handle);
        $this->assertEquals(0, $stream->tell());
        yield $stream->write('foo');
        $this->assertEquals(3, $stream->tell());
        $stream->seek(1);
        $this->assertEquals(1, $stream->tell());
        $this->assertSame(ftell($handle), $stream->tell());
        $stream->close();
    }

    public function testProvidesStreamPosition()
    {
        \coroutine_run($this->taskProvidesStreamPosition());
    }

    public function testCloseClearProperties()
    {
        $handle = fopen('php://temp', 'r+');
        $stream = AsyncStream::createFromResource($handle);
        $stream->close();

        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertNull($stream->getSize());
        $this->assertEmpty($stream->getMetadata());
    }

    public function taskCanDetachStream()
    {
        $r = fopen('php://temp', 'w+');
        $stream = AsyncStream::createFromResource($r);
        yield $stream->write('foo');

        $this->assertTrue($stream->isReadable());
        $this->assertSame($r, $stream->detach());

        $stream->detach();

        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());

        $throws = function (callable $fn) use ($stream) {
            try {
                $fn($stream);
                $this->fail();
            } catch (\Exception $e) {
                // Suppress the exception
            }
        };

        $throws(function (StreamInterface $stream) {
            yield $stream->read(10);
        });
        $throws(function (StreamInterface $stream) {
            yield $stream->write('bar');
        });
        $throws(function (StreamInterface $stream) {
            $stream->seek(10);
        });
        $throws(function (StreamInterface $stream) {
            $stream->tell();
        });
        $throws(function (StreamInterface $stream) {
            $stream->eof();
        });
        $throws(function (StreamInterface $stream) {
            $stream->getSize();
        });
        $throws(function (StreamInterface $stream) {
            yield $stream->getContents();
        });

        $this->assertSame('', (string) $stream);
        $stream->close();
    }

    public function testCanDetachStream()
    {
        \coroutine_run($this->taskCanDetachStream());
    }

    public function taskStreamReadingWithZeroLength()
    {
        $r      = fopen('php://temp', 'r');
        $stream = new AsyncStream($r);

        $this->assertSame('', yield $stream->read(0));

        $stream->close();
    }

    public function testStreamReadingWithZeroLength()
    {
        \coroutine_run($this->taskStreamReadingWithZeroLength());
    }

    public function taskStreamReadingWithNegativeLength()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Length parameter cannot be negative');

        $r = fopen('php://temp', 'r');
        $stream = new AsyncStream($r);

        try {
            yield $stream->read(-1);
        } catch (\Exception $e) {
            $stream->close();
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $e;
        }

        $stream->close();
    }

    public function testStreamReadingWithNegativeLength()
    {
        \coroutine_run($this->taskStreamReadingWithNegativeLength());
    }

    public function taskCopyReturnsDestinationStream()
    {
        $readable = new AsyncStream('hello');

        $r = fopen('php://temp', 'w+');
        $writable = new AsyncStream($r);

        $ret = yield AsyncStream::copyResource($readable, $writable);

        $this->assertSame($writable->getResource(), $ret->getResource());
        $this->assertSame('hello', yield $ret->getContents());

        $readable->close();
        $writable->close();
        $ret->close();
    }

    public function testCopyReturnsDestinationStream()
    {
        \coroutine_run($this->taskCopyReturnsDestinationStream());
    }

    public function taskCopyNullReturnsNewStream()
    {
        $readable = new AsyncStream('hello world');
        $ret = yield AsyncStream::copyResource($readable);

        $this->assertNotSame($readable->getResource(), $ret->getResource());
        $this->assertSame('hello world', yield $ret->getContents());

        $readable->close();
        $ret->close();
    }

    public function testCopyNullReturnsNewStream()
    {
        \coroutine_run($this->taskCopyNullReturnsNewStream());
    }

    public function testPair()
    {
        $socket = AsyncStream::pair();

        $this->assertInternalType('resource', $socket[0]);
        $this->assertInternalType('resource', $socket[1]);

        $this->assertSame('stream', get_resource_type($socket[0]));
        $this->assertSame('stream', get_resource_type($socket[1]));

        $string = 'test';

        fwrite($socket[0], $string);
        $this->assertSame($string, fread($socket[1], 8192));
    }

    public function taskBodyStream()
    {
        $request = $this->http->useZlib(true)->request('POST', self::TARGET_URLS . 'anything');
        $request = $request->withHeader('Content-Type', 'application/json; charset="utf-8"');
        $request = $request->withBody(AsyncStream::createFromFile(__FILE__, 'rb'));

        $response = yield $this->http->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $content = yield $response->getBody()->getContents();

        $this->assertEquals(
            file_get_contents(__FILE__),
            \json_decode($content, true)['data']
        );
    }

    /**
     * @requires extension zlib
     */
    public function testBodyStream()
    {
        \coroutine_run($this->taskBodyStream());
    }

    public function taskDeflateBodyStream()
    {
        $request = $this->http->useZlib(true)->request('POST', self::TARGET_URLS . 'anything');
        $request = $request->withHeader('Content-Type', 'application/json; charset="utf-8"');
        $request = $request->withBody(yield AsyncStream::createDeflateFromFile(__FILE__, 'rb+', true));

        $response = yield $this->http->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $content = yield $response->getBody()->getContents();

        $this->assertEquals(
            file_get_contents(__FILE__),
            \json_decode($content, true)['data']
        );
    }

    /**
     * @requires extension zlib
     */
    public function testDeflateBodyStream()
    {
        \coroutine_run($this->taskDeflateBodyStream());
    }
}
