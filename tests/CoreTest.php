<?php

declare(strict_types=1);

namespace Async\Tests;

use Async\Request\Body;
use Async\Request\Response;
use Async\Request\Request;
use Async\Coroutine\Exceptions\PanicInterface;
use Async\Request\Exception\RequestException;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    const TARGET_URL = "https://enev6g8on09tl.x.pipedream.net";
    const TARGET_URLS = "https://httpbin.org/";

    protected $websites = [
        'http://google.com/',
       // 'http://blogspot.com/',
        'http://creativecommons.org/',
        'http://microsoft.com/',
        'http://dell.com/',
        'http://nytimes.com/'
    ];

	protected function setUp(): void
    {
        \coroutine_clear();
        \http_clear_all();
        \response_clear_all();
    }

    public function task_head($websites)
    {
        foreach($websites as $website) {
            $tasks[] = yield \request(\http_head($website));
        }
        $this->assertCount(5, $tasks);

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
            $this->assertNotNull(\response_body($urlInstance));
            \response_clear($urlInstance);
        }, $responses);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(\BAD_ID);
        yield \request_abort(999999);
    }

    public function testRequestGet()
    {
        \coroutine_run($this->taskRequestGet());
    }

    public function taskRequestPost()
    {
        $httpBin = yield \request([Request::METHOD_POST, self::TARGET_URLS.'post', ["foo" => "bar"]]);
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
            $this->assertNotNull(\response_body($urlInstance));
            \response_clear($urlInstance);
        };

        \response_clear();
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage(\BAD_CALL);
        $this->assertNull(yield \response_xml());
    }

    public function testRequestPost()
    {
        \coroutine_run($this->taskRequestPost());
    }

    public function taskFetch()
    {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage(\BAD_ACCESS);
        $responses = yield \fetch(
            \http_options(self::TARGET_URL)
        );
    }

    public function testFetch()
    {
        \coroutine_run($this->taskFetch());
    }

    public function taskFetchFailSkip()
    {
        $pipedream = yield \request('pine', \http_post('pine', self::TARGET_URL, [Body::JSON, "foo" => "bar"]));
        $httpBin = yield \request('bin', \http_put('bin', self::TARGET_URLS.'put', ["foo" => "bar"]));
        $bad = yield \request(
            (new Request(Request::METHOD_OPTIONS, self::TARGET_URL))->withHeader('Content-Length', '4')
        );

        \fetchOptions(0, false);
        $responses = yield \fetch($pipedream, $httpBin, $bad);
        $this->assertCount(2, $responses);

        while (!\response_eof('bin')) {
            $echo = yield \response_stream('bin');
            $this->assertNotNull($echo);
        }

        \http_clear_all();
        \response_clear_all();
    }

    public function testFetchFailSkip()
    {
        \coroutine_run($this->taskFetchFailSkip());
    }

    public function taskFetchFail()
    {
        $pipedream = yield \request('pine', \http_post('pine', self::TARGET_URL, [Body::JSON, "foo" => "bar"]));
        $httpBin = yield \request('bin', \http_put('bin', self::TARGET_URLS.'put', ["foo" => "bar"]));
        $bad = yield \request(
            (new Request(Request::METHOD_OPTIONS, self::TARGET_URL))->withHeader('Content-Length', '4')
        );

		$this->expectException(RequestException::class);
        $responses = yield \fetch($pipedream, $httpBin, $bad);
    }

    public function testFetchFail()
    {
        \coroutine_run($this->taskFetchFail());
    }

    public function taskFetchFailInt()
    {
        $pipedream = yield \request('pine', \http_post('pine', self::TARGET_URL, [Body::JSON, "foo" => "bar"]));
        $httpBin = yield \request('bin', \http_put('bin', self::TARGET_URLS.'put', ["foo" => "bar"]));
        $bad = 'yield \request((new Request(Request::METHOD_OPTIONS, self::TARGET_URL)));';

		$this->expectException(PanicInterface::class);
        $responses = yield \fetch($pipedream, $httpBin, $bad);
    }

    public function testFetchFailInt()
    {
        \coroutine_run($this->taskFetchFailInt());
    }

    public function taskRequestPut()
    {
        $data['name'] = 'github';
        $data['key'] = 'XT3837';
        yield \request('bin', [Request::METHOD_PUT, self::TARGET_URLS.'put', $data]);
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

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage(\BAD_CALL);
        $this->assertEquals('{"success":true}', yield \response_body('bin'));
        \http_clear('bin');
    }

    public function testRequestPut()
    {
        \coroutine_run($this->taskRequestPut());
    }

    public function taskRequestPatch()
    {
        $data['name'] = 'github';
        $data['key'] = 'XT3837';
        yield \request('bin', [Request::METHOD_PATCH, self::TARGET_URLS.'patch', $data]);
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

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage(\BAD_CALL);
        $this->assertEquals('{"success":true}', yield \response_json('bin'));
        \http_clear('bin');
    }

    public function testRequestPatch()
    {
        \coroutine_run($this->taskRequestPatch());
    }

    public function taskRequestDelete()
    {
        $data['name'] = 'github';
        $data['key'] = 'XT3837';

        yield \request([Request::METHOD_DELETE, self::TARGET_URLS.'delete', $data]);
        yield \request('pipe', \http_delete('pipe', self::TARGET_URL, Body::create(Body::JSON, $data)));

        while (true) {
            if (\response_phrase() === null) {
                yield;
            } else {
                break;
            }
        }

        $this->assertTrue(\response_ok());
        $this->assertEquals(Response::STATUS_OK, \response_code());
        $this->assertSame("XT3837", (yield \response_json())->form->{'key'});
        \response_clear();

        $this->assertSame(true, (yield \response_json('pipe'))->success);
        \http_clear('pipe');
        \http_clear();
    }

    public function testRequestDelete()
    {
        \coroutine_run($this->taskRequestDelete());
    }

    public function taskRequestOptions()
    {
        yield \request('pipe', \http_options('pipe', self::TARGET_URL));
        yield \request([Request::METHOD_OPTIONS, self::TARGET_URLS.'options']);

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
        \response_clear('pipe');

        $this->assertTrue(\response_has(null, "Access-Control-Allow-Methods"));
        $this->assertEquals('', yield \response_body());
        \response_clear();
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
        \fetchOptions(3);
        $responses = yield \fetch([1]);
    }

    public function testRequestFailing()
    {
        \coroutine_run($this->taskRequestFailing());
    }
}
