<?php

declare(strict_types=1);

namespace Async\Tests;

use Async\Request\Body;
use Async\Request\Hyper;
use Async\Request\Request;
use Async\Request\Response;
use Psr\Http\Client\ClientExceptionInterface;
use PHPUnit\Framework\TestCase;

class HyperTest extends TestCase
{
    const TARGET_URL = "https://enev6g8on09tl.x.pipedream.net/";
    const TARGET_URLS = "https://httpbin.org/";
    protected $http;

	protected function setUp(): void
    {
        \coroutine_clear();
        $this->http = new Hyper;
    }

    public function task_get_response_received()
    {
        $response = yield $this->http->get(self::TARGET_URL);

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals('{"success":true}', yield $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("Content-Type"));
        $this->assertContains("application/json", $response->getHeaderLine("Content-Type"));
    }

    public function test_get_response_received()
    {
        \coroutine_run($this->task_get_response_received());
    }

    public function task_post_response_received()
    {
        $response = yield $this->http->post(self::TARGET_URL, new Body(Body::JSON, ["foo" => "bar"]));

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals('{"success":true}', yield $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("X-Powered-By"));
    }

    public function test_post_response_received()
    {
        \coroutine_run($this->task_post_response_received());
    }

    public function task_patch_response_received()
    {
        $response = yield $this->http->patch(self::TARGET_URL, new Body("foo"));

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals('{"success":true}', yield $response->getBody()->getContents());
        $this->assertEquals("application/json; charset=utf-8", $response->getHeaderLine("Content-Type"));
    }

    public function test_patch_response_received()
    {
        \coroutine_run($this->task_patch_response_received());
    }

    public function task_put_response_received()
    {
        $response = yield $this->http->put(self::TARGET_URL, new Body("foo", 'text/plain'));

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals('{"success":true}', yield $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("x-pd-status"));
    }

    public function test_put_response_received()
    {
        \coroutine_run($this->task_put_response_received());
    }

    public function task_delete_response_received()
    {
        $response = yield $this->http->delete(self::TARGET_URL, new Body("foo"));

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals('{"success":true}', yield $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("Date"));
    }

    public function test_delete_response_received()
    {
        \coroutine_run($this->task_delete_response_received());
    }

    public function task_head_response_received()
    {
        $response = yield $this->http->head(self::TARGET_URL);

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals("", yield $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("Access-Control-Allow-Origin"));
    }

    public function test_head_response_received()
    {
        \coroutine_run($this->task_head_response_received());
    }

    public function task_options_response_received()
    {
        $response = yield $this->http->options(self::TARGET_URL);

        $this->assertEquals(Response::STATUS_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals("", yield $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("Access-Control-Allow-Methods"));
    }

    public function test_options_response_received()
    {
        \coroutine_run($this->task_options_response_received());
    }

    public function task_send_request_with_added_headers()
    {
        $response = yield $this->http->get(self::TARGET_URLS.'get',
            [],
            ['X-Added-Header' => 'Symplely!'],
            ['timeout' => 5]
        );

        $this->assertTrue($response->hasHeader('X-Content-Type-Options'));
        $this->assertContains("X-Added-Header", yield $response->getBody()->getContents());
    }

    public function test_send_request_with_added_headers()
    {
        \coroutine_run($this->task_send_request_with_added_headers());
    }


	public function taskSendRequest()
	{
		try {
			$url = self::TARGET_URLS.'get';
			$response = yield $this->http->sendRequest(new Request(Request::METHOD_GET, $url));
			$json = \json_decode(yield $response->getBody()->getContents());

			$this->assertSame($url, $json->url);
			$this->assertSame(\SYMPLELY_USER_AGENT, $json->headers->{'User-Agent'});
			$this->assertSame(Response::STATUS_OK, $response->getStatusCode());
		} catch(\Exception $e) {
			$this->markTestSkipped('httpbin.org error: '.$e->getMessage());
		}

	}

    public function testSendRequest()
    {
        \coroutine_run($this->taskSendRequest());
    }

    public function taskRequest()
    {
        $response = yield $this->http->request(Request::METHOD_GET, self::TARGET_URLS.'bearer', null, ['type' => 'bearer', 'token' => '2323@#$@']);

            $json = \json_decode(yield $response->getBody()->getContents());

            $this->assertSame(Response::STATUS_OK, $response->getStatusCode());
			$this->assertSame(true, $json->authenticated);
			$this->assertSame('2323@#$@', $json->token);
	}

    public function testRequest()
    {
        \coroutine_run($this->taskRequest());
    }

    public function taskNetworkError()
    {
		$this->expectException(ClientExceptionInterface::class);

		yield $this->http->sendRequest(new Request(Request::METHOD_GET, 'http://foo'));
    }

    public function testNetworkError()
    {
        \coroutine_run($this->taskNetworkError());
    }
}
