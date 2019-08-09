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
        $response = yield $this->http->get(self::TARGET_URLS.'get', [], [
            'X-Added-Header' => 'Symplely!',
        ], ['timeout' => 5]);

        $this->assertTrue($response->hasHeader('X-Content-Type-Options'));
        //$this->assertEquals("", yield $response->getBody()->getContents());
        $this->assertEquals('Symplely!', $response->getHeader('X-Added-Header'));
    }

    public function test_send_request_with_added_headers()
    {
        \coroutine_run($this->task_send_request_with_added_headers());
    }


	public function testSendRequest()
	{
		try {
			$url      = self::TARGET_URLS.'get';
			$response = yield $this->http->sendRequest(new Request(Request::METHOD_GET, $url));
			$json     = json_decode($response->getBody()->getContents());

			$this->assertSame($url, $json->url);
			$this->assertSame(\SYMPLELY_USER_AGENT, $json->headers->{'User-Agent'});
			$this->assertSame(Response::STATUS_OK, $response->getStatusCode());
			$this->assertSame(Response::STATUS_OK, $response->getStatusCode());
		} catch(\Exception $e) {
			$this->markTestSkipped('httpbin.org error: '.$e->getMessage());
		}

	}

	public function requestDataProvider():array {
		return [
			'get'        => ['get',    []],
			'post'       => ['post',   []],
			'post-json'  => ['post',   ['Content-type' => 'application/json']],
			'post-form'  => ['post',   ['Content-type' => 'application/x-www-form-urlencoded']],
			'put-json'   => ['put',    ['Content-type' => 'application/json']],
			'put-form'   => ['put',    ['Content-type' => 'application/x-www-form-urlencoded']],
			'patch-json' => ['patch',  ['Content-type' => 'application/json']],
			'patch-form' => ['patch',  ['Content-type' => 'application/x-www-form-urlencoded']],
			'delete'     => ['delete', []],
		];
	}

	/**
	 * @dataProvider requestDataProvider
	 *
	 * @param $method
	 * @param $extra_headers
	 */
    public function testRequest(string $method, array $extra_headers)
    {
		try {
			$response = yield $this->http->request(
				\strtoupper($method),
				self::TARGET_URLS.$method, [
				    ['foo' => 'bar'],
				    ['huh' => 'wtf'],
                    ['what' => 'nope'] + $extra_headers
                ]
			);

		} catch(\Exception $e) {
			$this->markTestSkipped('httpbin.org error: '.$e->getMessage());
		}

		$json = \json_decode(yield $response->getBody()->getContents());

		if (!$json) {
			$this->markTestSkipped('empty response');
		} else {
			$this->assertSame(self::TARGET_URLS.$method.'?foo=bar', $json->url);
			$this->assertSame('bar', $json->args->foo);
			$this->assertSame('nope', $json->headers->What);
			$this->assertSame(\SYMPLELY_USER_AGENT, $json->headers->{'User-Agent'});

			if (in_array($method, ['patch', 'post', 'put'])) {
				if (isset($extra_headers['content-type']) && $extra_headers['content-type'] === 'application/json') {
					$this->assertSame('wtf', $json->json->huh);
				} else {
					$this->assertSame('wtf', $json->form->huh);
				}
			}
		}

	}

    public function testNetworkError()
    {
		$this->expectException(ClientExceptionInterface::class);

		yield $this->http->sendRequest(new Request(Request::METHOD_GET, 'http://foo'));
    }
}
