<?php

declare(strict_types=1);

namespace Async\Tests;

use Async\Request\Body;
use Async\Request\Response;
use Async\Request\Request;
use Async\Coroutine\Exceptions\Panicking;
use Async\Request\Exception\RequestException;
use Async\Request\Hyper;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    const TARGET_URL = "https://enev6g8on09tl.x.pipedream.net";
    const TARGET_URLS = "https://httpbin.org/";
    const TARGET_URLS_ = "https://httpbin.org/status/";

    protected $websites = [
        'http://google.com/',
        'http://blogspot.com/',
        'http://creativecommons.org/',
        'http://microsoft.com/',
        'https://dell.com/',
        'https://nytimes.com/'
    ];

    protected function setUp(): void
    {
        \coroutine_clear();
        \hyper_clear();
    }

    public function task_head($websites)
    {
        foreach ($websites as $website) {
            $tasks[] = yield \request(\http_head($website));
        }
        $this->assertCount(\count($this->websites), $tasks);

        $responses = yield \fetch_await($tasks, 3);

        global $__uri__;
        $this->assertInstanceOf(\Async\Request\HyperInterface::class, $__uri__);

        $this->assertCount(3, $responses);
        $statuses = ['200' => 0, '400' => 0];
        \array_map(function ($instance) use (&$statuses) {
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

        $this->expectOutputRegex('/[{^\[.+\] (\w+) (.+)?}]/');
        yield \http_printLogs();
        $this->assertGreaterThanOrEqual(7, \count(yield \http_closeLog()));
        \http_clear();
        \response_clear();
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
        yield \hyper_shutdown();
    }

    public function testRequestHead()
    {
        \coroutine_run($this->taskRequestHead());
    }

    public function taskRequestGet()
    {
        $pipedream = yield \request(new Request(Request::METHOD_GET, 'https://facebook.com'));
        $httpBin = yield \request([Request::METHOD_GET, self::TARGET_URLS_ . '200']);
        $times = yield \request(\http_get('https://nytimes.com'));

        $responses = yield \fetch($pipedream, $httpBin, $times);
        $this->assertCount(3, $responses);
        \array_map(function ($urlInstance) {
            $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $urlInstance);
            $this->assertTrue(\response_ok($urlInstance));
            $this->assertEquals(Response::STATUS_OK, \response_code($urlInstance));
            $ok = \response_phrase($urlInstance);
            $this->assertEquals(Response::REASON_PHRASES[200], $ok);
            $this->assertNotNull(yield \response_body($urlInstance));
            \response_clear($urlInstance);
        }, $responses);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\BAD_ID);
        yield \request_abort(999999);
        yield \hyper_shutdown();
    }

    public function testRequestGet()
    {
        \coroutine_run($this->taskRequestGet());
    }

    public function taskRequestPost()
    {
        $httpBin = yield \request([Request::METHOD_POST, self::TARGET_URLS . 'post', ["foo" => "bar"]]);
        $pipedream = yield \request(\http_post(self::TARGET_URL, [Body::JSON, "foo" => "bar"]));

        $responses = yield \fetch($pipedream, $httpBin);
        $this->assertCount(2, $responses);

        foreach ($responses as $key => $urlInstance) {
            $this->assertTrue(\is_type($key, 'int'));
            $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $urlInstance);
            $this->assertTrue(\response_ok($urlInstance));
            $this->assertEquals(Response::STATUS_OK, \response_code($urlInstance));
            $ok = \response_phrase($urlInstance);
            $this->assertEquals(Response::REASON_PHRASES[200], $ok);
            $this->assertNotNull(yield \response_body($urlInstance));
            \response_clear($urlInstance);
        };

        \response_clear();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(\BAD_CALL);
        $this->assertNull(yield \response_xml());

        yield \hyper_shutdown();
    }

    public function testRequestPost()
    {
        \coroutine_run($this->taskRequestPost());
    }

    public function taskFetch()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid access, only array of integers, or generator objects allowed!');
        $responses = yield \fetch(
           '\http_options(self::TARGET_URL)'
        );

        yield \hyper_shutdown();
    }

    public function testFetch()
    {
        \coroutine_run($this->taskFetch());
    }

    public function taskFetchFailSkip()
    {
        $pipedream = yield \request('pine', \http_post('pine', self::TARGET_URL, [Body::JSON, "foo" => "bar"]));
        $httpBin = yield \request('bin', \http_put('bin', self::TARGET_URLS . 'put', ["foo" => "bar"]));
        $bad = yield \request(
            (new Request(Request::METHOD_OPTIONS, self::TARGET_URL))->withHeader('Content-Length', '4')
        );

        $responses = yield \fetch_await([$pipedream, $httpBin, $bad], 0, false);
        $this->assertInstanceOf(\Throwable::class, $responses[$bad]);

        while (!\response_eof('bin')) {
            $echo = yield \response_stream('bin');
            $this->assertNotNull($echo);
        }

        yield \hyper_shutdown();
    }

    public function testFetchFailSkip()
    {
        \coroutine_run($this->taskFetchFailSkip());
    }

    public function taskFetchFail()
    {
        $pipedream = yield \request('pine', \http_post('pine', self::TARGET_URL, [Body::JSON, "foo" => "bar"]));
        $httpBin = yield \request('bin', \http_put('bin', self::TARGET_URLS . 'put', ["foo" => "bar"]));
        $bad = yield \request(
            (new Request(Request::METHOD_OPTIONS, self::TARGET_URL))->withHeader('Content-Length', '4')
        );

        //$this->expectException(RequestException::class);
        $responses = yield \fetch($pipedream, $httpBin, $bad);
    }

    public function testFetchFail()
    {
        \coroutine_run($this->taskFetchFail());
    }

    public function taskFetchFailInt()
    {
        $pipedream = yield \request('pine', \http_post('pine', self::TARGET_URL, [Body::JSON, "foo" => "bar"]));
        $httpBin = yield \request('bin', \http_put('bin', self::TARGET_URLS . 'put', ["foo" => "bar"]));
        $bad = 'yield \request((new Request(Request::METHOD_OPTIONS, self::TARGET_URL)));';

        $this->expectException(Panicking::class);
        $responses = yield \fetch($pipedream, $httpBin, $bad);
        yield \hyper_shutdown();
    }

    public function testFetchFailInt()
    {
        \coroutine_run($this->taskFetchFailInt());
    }

    public function taskRequestPut()
    {
        $data['name'] = 'github';
        $data['key'] = 'XT3837';
        yield \request('bin', [Request::METHOD_PUT, self::TARGET_URLS . 'put', $data]);
        yield \request('pipe', \http_put('pipe', self::TARGET_URL, $data));

        while (true) {
            if (\response_ok('pipe') === null) {
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

        \response_clear('bin');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(\BAD_CALL);
        $this->assertEquals('{"success":true}', yield \response_body('bin'));
        yield \hyper_shutdown();
    }

    public function testRequestPut()
    {
        \coroutine_run($this->taskRequestPut());
    }

    public function taskRequestPatch()
    {
        $data['name'] = 'github';
        $data['key'] = 'XT3837';
        yield \request('bin', [Request::METHOD_PATCH, self::TARGET_URLS . 'patch', $data]);
        yield \request('pipe', \http_patch('pipe', self::TARGET_URL, $data));

        while (true) {
            if (\response_code('pipe') === null) {
                yield;
            } else {
                break;
            }
        }

        $this->assertTrue(\response_ok('pipe'));
        $ok = \response_phrase('pipe');
        $this->assertEquals(Response::REASON_PHRASES[200], $ok);
        $this->assertEquals('{"success":true}', yield \response_body('pipe'));
        \response_clear('pipe');

        \http_clear('bin');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(\BAD_CALL);
        $this->assertEquals('{"success":true}', yield \response_json('bin'));
        yield \hyper_shutdown();
    }

    public function testRequestPatch()
    {
        \coroutine_run($this->taskRequestPatch());
    }

    public function taskRequestDelete()
    {
        $data['name'] = 'github';
        $data['key'] = 'XT3837';

        yield \request([Request::METHOD_DELETE, self::TARGET_URLS . 'delete', $data]);
        yield \request('pipe', \http_delete('pipe', self::TARGET_URL, Body::create(Body::JSON, $data)));

        while (true) {
            if (\response_phrase('pipe') === null) {
                yield;
            } else {
                break;
            }
        }

        \response_clear('pipe');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(\BAD_CALL);
        $this->assertSame(true, (yield \response_json('pipe'))->success);
        yield \hyper_shutdown();
    }

    public function testRequestDelete()
    {
        \coroutine_run($this->taskRequestDelete());
    }

    public function taskRequestOptions()
    {
        yield \request('pipe', \http_options('pipe', self::TARGET_URL));
        yield \request([Request::METHOD_OPTIONS, self::TARGET_URLS . 'options']);

        while (true) {
            if (\response_header('pipe', "Access-Control-Allow-Methods") === null) {
                yield;
            } else {
                break;
            }
        }

        $this->assertTrue(\response_ok('pipe'));
        $this->assertTrue(\response_has('pipe', "Access-Control-Allow-Methods"));
        $this->assertEquals(Response::STATUS_NO_CONTENT, \response_code('pipe'));
        $this->assertEquals("", yield \response_body('pipe'));
        yield \hyper_shutdown();
    }

    public function testRequestOptions()
    {
        \coroutine_run($this->taskRequestOptions());
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
        $responses = yield \fetch_await([1], 3);
        yield \hyper_shutdown();
    }

    public function testRequestFailing()
    {
        \coroutine_run($this->taskRequestFailing());
    }
}
