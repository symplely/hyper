<?php

declare(strict_types=1);

namespace Async\Tests;

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
        \http_clear();
        $this->assertNull($__uri__);

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

        return \json_encode($statuses);
    }

    public function taskRequestHead()
    {
        $int = yield \request(\http_head('test', self::TARGET_URL));
        $this->assertEquals('int', \is_type($int));
        yield \request_abort($int);
        $response = yield \http_head('test');
        $this->assertFalse($response);
        \http_clear('test');
        $response = yield \http_head();
        $this->assertFalse($response);
        \http_clear();
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
        $request = new Request(Request::METHOD_GET, self::TARGET_URL);
        $pipedream = yield \request($request);
        $httpBin = yield \request([Request::METHOD_GET, self::TARGET_URLS.'get']);
        //$creativeCommons = yield \request(\http_get('http://creativecommons.org/'));

        $responses = yield \fetch($pipedream, $httpBin);
        //\http_clear();
        $this->assertCount(2, $responses);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $responses[$pipedream]);
        $urlInstance = $responses[$pipedream];
        $this->assertTrue(\response_ok($urlInstance));
        $this->assertEquals(Response::STATUS_OK, \response_code($urlInstance));
        $ok = \response_phrase($urlInstance);
        $this->assertEquals(Response::REASON_PHRASES[200], $ok);
        $this->assertNotNull(yield \response_body($urlInstance));

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $responses[$httpBin]);
        $urlsInstance = $responses[$httpBin];
        $this->assertTrue(\response_ok($urlsInstance));
        $this->assertEquals(Response::STATUS_OK, \response_code($urlsInstance));
        $ok = \response_phrase($urlsInstance);
        $this->assertEquals(Response::REASON_PHRASES[200], $ok);
        $this->assertEquals('{"success":true}', yield \response_body($urlInstance));

       // $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $responses[$creativeCommons]);
       // $getInstance = $responses[$creativeCommons];
       // $this->assertTrue(\response_ok($getInstance));
      //  $this->assertEquals(Response::STATUS_OK, \response_code($getInstance));
      //  $ok = \response_phrase($getInstance);
      //  $this->assertEquals(Response::REASON_PHRASES[200], $ok);
     //   $this->assertNotNull(yield \response_body($getInstance));
    }

    public function taskRequestGetUrl()
    {
        yield $this->taskRequestGet($this->websites);
    }

    public function testRequestGet()
    {
        \coroutine_run($this->taskRequestGetUrl());
    }
}
