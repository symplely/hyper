<?php

declare(strict_types=1);

namespace Async\Tests;

use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    const TARGET_URL = "https://enev6g8on09tl.x.pipedream.net";
    //const TARGET_URL = "https://httpbin.org/";
    private $websites = [
        'http://google.com/',
        'http://blogspot.com/',
        'http://creativecommons.org/'
    ];

	protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function get_statuses($websites)
    {
        $statuses = ['200' => 0, '400' => 0];
        foreach($websites as $website) {
            $tasks[] = yield \await($this->get_website_status($website));
        }

        $taskStatus = yield \gather($tasks);
        $this->assertEquals(3, \count($taskStatus));
        \array_map(function($ok) use (&$statuses) {
            if ($ok == 200) {
                $statuses['200']++;
            } elseif ($ok == 400) {
                $statuses['400']++;
            }
        }, $taskStatus);

        return \json_encode($statuses);
    }

    public function get_website_status($url)
    {
        $response = yield \http_head($url);
        \http_clear();
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $response);
        $status = $response->getStatusCode();
        $this->assertEquals(200, $status);
        return yield $status;
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
        $data = yield from $this->get_statuses($this->websites);
        $this->expectOutputString('{"200":3,"400":0}');
        print $data;
    }

    public function testRequestHead()
    {
        \coroutine_run($this->taskRequestHead());
    }
}
