# hyper

A simple asynchronous PSR-18 HTTP client using coroutines.

**This package is under development, all `asynchronous` parts has not been fully implemented. The proper `async` way to make PSR-18 `request`/calls or handle `response`/results, not tested, nor added.**

## Installation

```text
composer require symplely/hyper
```

## Making requests: The easy way

The quickest and easiest way to begin making requests is to use the HTTP method name:

```php
use Async\Request\Hyper;

$http = new Hyper;

$response = $http->get("https://www.google.com");
$response = $http->post("https://example.com/search", ["Form data"]));
```

This library has built-in methods to support the major HTTP verbs: get, post, put, patch, delete, head, and options. However, you can make **any** HTTP verb request using the **request** method directly.

```php
$response = $http->request("connect", "https://api.example.com/v1/books");
```

## Handling responses

Responses in Shuttle implement PSR-7 ResponseInterface and as such are streamable resources.

```php
$response = $http->get("https://api.example.com/v1/books");

echo $response->getStatusCode(); // 200
echo $response->getReasonPhrase(); // OK
echo $response->isSuccessful(); // true

$body = $response->getBody()->getContents();
```

## Handling failed requests

This library will throw a `RequestException` by default if the request failed. This includes things like host name not found, connection timeouts, etc.

Responses with HTTP 4xx or 5xx status codes *will not* throw an exception and must be handled properly within your business logic.

## Making requests: The PSR-7 way

If code reusability and portability is your thing, future proof your code by making requests the PSR-7 way. Remember, PSR-7 stipulates that Request and Response messages be immutable.

```php
use Async\Request\Uri;
use Async\Request\Request;
use Async\Request\Hyper;

// Build Request message.
$request = new Request;
$request = $request
    ->withMethod("get")
    ->withUri(new Uri("https://www.google.com"))
    ->withHeader("Accept-Language", "en_US");

// Send the Request.
$http = new Hyper;
$response = $http->sendRequest($request);
```

## Options

* `headers` An array of key & value pairs to pass in with each request.

## Request bodies

An easy way to submit data with your request is to use the `Body` class. This class will automatically
transform the data, convert to a **BufferStream**, and set a default **Content-Type** header on the request.

Pass one of the following **CONSTANTS**, onto the class constructor will:

* `Body::JSON` Convert an associative array into JSON, sets `Content-Type` header to `application/json`.
* `Body::FORM` Convert an associative array into a query string, sets `Content-Type` header to `application/x-www-form-urlencoded`.
* `Body::XML` Does no conversion of data, sets `Content-Type` header to `application/xml`.
* `Body::FILE` Does no conversion of data, will detect and set `Content-Type` header.
* `Body::MULTI` Does no conversion of data, sets `Content-Type` header to `multipart/form-data`.

To submit a JSON payload with a request:

```php
use Async\Request\Body;

$book = [
    "title" => "Breakfast Of Champions",
    "author" => "Kurt Vonnegut",
];

$http->post("https://api.example.com/v1/books", [Body::JSON, $book]);
// Or
$http->post("https://api.example.com/v1/books", new Body(Body::JSON, $book));
// Or
$http->post("https://api.example.com/v1/books", Body::create(Body::JSON, $book));
```
