<?php

declare(strict_types=1);

namespace Async\Tests;

use Async\Request\Body;
use Async\Request\Response;
use Async\Request\Request;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    const TARGET_URL = "https://enev6g8on09tl.x.pipedream.net";
    const TARGET_URLS = "https://httpbin.org/";

    protected $websites = [
        'http://google.com/',
        'http://blogspot.com/',
        'http://creativecommons.org/',
        'http://microsoft.com/',
        'http://dell.com/',
        'http://nytimes.com/'
    ];

	protected function setUp(): void
    {
        \coroutine_clear();
        \http_clear();
        \response_clear();
    }

    public function task_head($websites)
    {
        foreach($websites as $website) {
            $tasks[] = yield \request(\http_head($website));
        }
        $this->assertCount(6, $tasks);

        \fetchOptions(3);
        $responses = yield \fetch($tasks);

        global $__uri__;
        $this->assertInstanceOf(\Async\Request\HyperInterface::class, $__uri__);

        $this->assertCount(3, $responses);
        $statuses = ['200' => 0, '400' => 0];
        \array_map(function($instance) use (&$statuses) {
            $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $instance);
            $this->assertTrue(\response_ok($instance));
            $this->assertEquals(Response::STATUS_OK, \response_code($instance));
            $ok = \response_phrase($instance);
            $this->assertEquals(Response::REASON_PHRASES[200], $ok);
            if ($ok == Response::REASON_PHRASES[200]) {
                $statuses['200']++;
            } elseif ($ok == Response::REASON_PHRASES[400]) {
                $statuses['400']++;
            }
        }, $responses);

        \http_clear();
        $this->assertNull($__uri__);

        return \json_encode($statuses);
    }

    public function taskRequestHead()
    {
        $int = yield \request(\http_head('test', self::TARGET_URL));
        $this->assertEquals('int', \is_type($int));
        yield \request_abort($int);
        $data = yield from $this->task_head($this->websites);
        $this->expectOutputString('{"200":3,"400":0}');
        print $data;
    }

    public function testRequestHead()
    {
        \coroutine_run($this->taskRequestHead());
    }

    public function taskRequestGet()
    {
        $pipedream = yield \request(new Request(Request::METHOD_GET, self::TARGET_URL));
        $httpBin = yield \request([Request::METHOD_GET, self::TARGET_URLS.'get']);
        $times = yield \request(\http_get('http://nytimes.com'));

        $responses = yield \fetch($pipedream, $httpBin, $times);
        $this->assertCount(3, $responses);

        \array_map(function($urlInstance) {
            $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $urlInstance);
            $this->assertTrue(\response_ok($urlInstance));
            $this->assertEquals(Response::STATUS_OK, \response_code($urlInstance));
            $ok = \response_phrase($urlInstance);
            $this->assertEquals(Response::REASON_PHRASES[200], $ok);
            $this->assertNotNull(yield \response_body($urlInstance));
            \response_clear($urlInstance);
        }, $responses);
    }

    public function testRequestGet()
    {
        \coroutine_run($this->taskRequestGet());
    }

    public function taskRequestPost()
    {
        $httpBin = yield \request(\http_post(self::TARGET_URLS.'post', ["foo" => "bar"]));
        $pipedream = yield \request(\http_post(self::TARGET_URL, [Body::JSON, "foo" => "bar"]));

        $responses = yield \fetch($pipedream, $httpBin);
        $this->assertCount(2, $responses);

        foreach($responses as $key => $urlInstance) {
            $this->assertTrue(\is_type($key, 'int'));
            $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $urlInstance);
            $this->assertTrue(\response_ok($urlInstance));
            $this->assertEquals(Response::STATUS_OK, \response_code($urlInstance));
            $ok = \response_phrase($urlInstance);
            $this->assertEquals(Response::REASON_PHRASES[200], $ok);
            $this->assertNotNull(yield \response_body($urlInstance));
        };
    }

    public function testRequestPost()
    {
        \coroutine_run($this->taskRequestPost());
    }

    public function taskRequestPut()
    {
        $data['name'] = 'github';
        $data['key'] = 'XT3837';
        yield \request('bin', [Request::METHOD_PUT, self::TARGET_URLS.'put', $data]);
        yield \request('pipe', \http_put('pipe', self::TARGET_URL, $data));

        while (true) {
            if (\response_ok('pipe') == null) {
                yield;
            } else {
                break;
            }
        }

        $this->assertEquals(Response::STATUS_OK, \response_code('pipe'));
        $ok = \response_phrase('pipe');
        $this->assertEquals(Response::REASON_PHRASES[200], $ok);
        $this->assertEquals('{"success":true}', yield \response_body('pipe'));
        \response_clear('pipe');

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage(\BAD_CALL);
        $this->assertEquals('{"success":true}', yield \response_body('bin'));
        \http_clear('bin');
    }

    public function testRequestPut()
    {
        \coroutine_run($this->taskRequestPut());
    }

    public function taskRequestFailing()
    {
        $response = yield \http_options('test');
        $this->assertFalse($response);
        $response = yield \http_options();
        $this->assertFalse($response);

        $response = yield \http_head('test');
        $this->assertFalse($response);
        $response = yield \http_head();
        $this->assertFalse($response);

        $response = yield \http_get('test');
        $this->assertFalse($response);
        $response = yield \http_get();
        $this->assertFalse($response);

        $response = yield \http_post('test');
        $this->assertFalse($response);
        $response = yield \http_post();
        $this->assertFalse($response);

        $response = yield \http_put('test');
        $this->assertFalse($response);
        $response = yield \http_put();
        $this->assertFalse($response);

        $response = yield \http_patch('test');
        $this->assertFalse($response);
        $response = yield \http_patch();
        $this->assertFalse($response);

        $response = yield \http_delete('test');
        $this->assertFalse($response);
        $response = yield \http_delete();
        $this->assertFalse($response);

		$this->expectException(\LengthException::class);
        \fetchOptions(3);
        $responses = yield \fetch([1]);
    }

    public function testRequestFailing()
    {
        \coroutine_run($this->taskRequestFailing());
    }
}
