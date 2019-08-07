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
    const TARGET_URL = "https://enev6g8on09tl.x.pipedream.net";
    //const TARGET_URL = "https://httpbin.org/";
    protected $http;

	protected function setUp(): void
    {
        \coroutine_clear();
        $this->http = new Hyper;
    }

    public function test_get_response_received()
    {
        $http = new Hyper;

        $response = yield $http->get(self::TARGET_URL);

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals('{"success":true}', $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("Content-Type"));
        $this->assertEquals("Content-Type: application/json", $response->getHeaderLine("Content-Type"));
    }

    public function test_post_response_received()
    {
        $http = new Hyper;

        $response = yield $http->post(self::TARGET_URL, Body::json(['name' => 'Symplely Hyper']));

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('{"success":true}', yield $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("Content-Type"));
        $this->assertEquals("Content-Type: application/json", $response->getHeaderLine("Content-Type"));
    }

    public function test_patch_response_received()
    {
        $http = new Hyper;

        $response = yield $http->patch(self::TARGET_URL, new BufferBody("foo"));

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals('{"success":true}', $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("Content-Type"));
        $this->assertEquals("Content-Type: text/plain", $response->getHeaderLine("Content-Type"));
    }

    public function test_put_response_received()
    {
        $http = new Hyper;

        $response = yield $http->put(self::TARGET_URL, new BufferBody("foo"));

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals('{"success":true}', $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("Content-Type"));
        $this->assertEquals("Content-Type: text/plain", $response->getHeaderLine("Content-Type"));
    }

    public function test_delete_response_received()
    {
        $http = new Hyper;

        $response = yield $http->delete(self::TARGET_URL);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals("", $response->getBody()->getContents());
    }

    public function test_head_response_received()
    {
        $http = new Hyper;

        $response = yield $http->head(self::TARGET_URL);

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals("", $response->getBody()->getContents());
    }

    public function test_options_response_received()
    {
        $http = new Hyper;

        $response = yield $http->options(self::TARGET_URL);

        $this->assertEquals(Response::STATUS_OK, $response->getStatusCode());
        $this->assertEquals("", $response->getBody()->getContents());
    }

    public function test_send_request_with_default_headers()
    {
        $http = new Hyper;

        $response = yield $http->get(self::TARGET_URL);

        $this->assertTrue($response->hasHeader('X-Powered-By'));
        $this->assertEquals('PHP/' . \PHP_VERSION, $response->getHeader('X-Powered-By')[0]);
    }

    public function test_send_request_with_added_headers()
    {
        $http = new Hyper;

        $response = yield $http->get(self::TARGET_URL, [], [
            'X-Added-Header' => 'Capsule!',
        ]);

        $this->assertTrue($response->hasHeader('X-Added-Header'));
        $this->assertEquals('Capsule!', $response->getHeader('X-Added-Header')[0]);
    }

	public function testSendRequest()
	{

		try {
			$url      = 'https://httpbin.org/get';
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
	public function testRequest(string $method, array $extra_headers){

		try{
			$response = yield $this->http->request(
				$method,
				'https://httpbin.org/'.$method, [
				    ['foo' => 'bar'],
				    ['huh' => 'wtf'],
                    ['what' => 'nope'] + $extra_headers
                ]
			);

		}
		catch(\Exception $e){
			$this->markTestSkipped('httpbin.org error: '.$e->getMessage());
		}

		$json = json_decode($response->getBody()->getContents());

		if(!$json){
			$this->markTestSkipped('empty response');
		}
		else{
			$this->assertSame('https://httpbin.org/'.$method.'?foo=bar', $json->url);
			$this->assertSame('bar', $json->args->foo);
			$this->assertSame('nope', $json->headers->What);
			$this->assertSame(\SYMPLELY_USER_AGENT, $json->headers->{'User-Agent'});

			if(in_array($method, ['patch', 'post', 'put'])){

				if(isset($extra_headers['content-type']) && $extra_headers['content-type'] === 'application/json'){
					$this->assertSame('wtf', $json->json->huh);
				}
				else{
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

    public function get_statuses($websites)
    {
        $statuses = ['200' => 0, '400' => 0];
        foreach($websites as $website) {
            $tasks[] = yield \await([$this, 'get_website_status'], $website);
        }

        $taskStatus = yield \gather($tasks);
        $this->assertEquals(3, \count($taskStatus));
        foreach($taskStatus as  $id => $status) {
            if (!$status)
                $statuses[$status] = 0;
            else {
                $statuses[$status] += 1;
                $this->assertEquals(200, $status);
            }
        }

        return json_encode($statuses);
    }

    public function get_website_status($url)
    {
        $response = yield \head_uri();
        $this->assertFalse($response);
        $response = yield \head_uri($url);
        \clear_uri();
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $response);
        $status = $response->getStatusCode();
        $this->assertEquals(200, $status);
        return $status;
    }

    public function taskFileOpen()
    {
        $instance = yield file_open(__DIR__.\DS.'list.txt');
        $websites = yield file_lines($instance);
        file_close($instance);
        if ($websites !== false) {
            $data = yield from $this->get_statuses($websites);
            $this->expectOutputString('{"200":3,"400":0}');
            print $data;
        }
    }

    public function testFileOpen()
    {
        \coroutine_run($this->taskFileOpen());
    }
}
