# hyper

[![Build Status](https://travis-ci.org/symplely/hyper.svg?branch=master)](https://travis-ci.org/symplely/hyper)[![Build status](https://ci.appveyor.com/api/projects/status/0l48ubuakc6wtqqm/branch/master?svg=true)](https://ci.appveyor.com/project/techno-express/hyper/branch/master)[![codecov](https://codecov.io/gh/symplely/hyper/branch/master/graph/badge.svg)](https://codecov.io/gh/symplely/hyper)[![Codacy Badge](https://api.codacy.com/project/badge/Grade/d902c3aa05d74df699aa9e962e70f63d)](https://www.codacy.com/app/techno-express/hyper?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=symplely/hyper&amp;utm_campaign=Badge_Grade)[![Maintainability](https://api.codeclimate.com/v1/badges/db8ee4adb142ffad35c9/maintainability)](https://codeclimate.com/github/symplely/hyper/maintainability)

A simple asynchronous PSR-18 HTTP client using coroutines.

**This package is under development, all `asynchronous` parts has not been fully implemented. The proper `async` way to make PSR-18 `request`/calls or handle `response`/results, not tested, nor added.**

## Installation

```text
composer require symplely/hyper
```

## Making requests: The easy old fashion way, with one caveat, need to be prefix with yield

The quickest and easiest way to begin making requests is to use the HTTP method name:

```php
use Async\Request\Hyper;

function main() {
    $http = new Hyper;

    $response = yield $http->get("https://www.google.com");
    $response = yield $http->post("https://example.com/search", ["Form data"]));
```

This library has built-in methods to support the major HTTP verbs: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, and `OPTIONS`. However, you can make **any** HTTP verb request using the **request** method directly, that returns an _PSR-7_ `RequestInterface`.

```php
    $request = $http->request("connect", "https://api.example.com/v1/books");
    $response = yield $http->sendRequest($request);
```

## Handling responses

Responses in *Hyper* implement _PSR-7_ `ResponseInterface` and as such are streamable resources.

```php
    $response = $http->get("https://api.example.com/v1/books");

    echo $response->getStatusCode(); // 200
    echo $response->getReasonPhrase(); // OK

    // The body is return asynchronous in an non-blocking mode,
    // and as such needs to be prefixed with `yield`
    $body = yield $response->getBody()->getContents();
}

// All coroutines/async/await needs to be enclosed/bootstrapped
// in one `main` entrance routine function to properly execute.
// The function `MUST` have at least one `yield` statement.
\coroutine_run(\main());
```

## Handling failed requests

This library will throw a `RequestException` by default if the request failed. This includes things like host name not found, connection timeouts, etc.

Responses with HTTP 4xx or 5xx status codes *will not* throw an exception and must be handled properly within your business logic.

## Making requests: The PSR-7 way, with one caveat, need to be prefix with yield

If code reusability and portability is your thing, future proof your code by making requests the PSR-7 way. Remember, PSR-7 stipulates that Request and Response messages be immutable.

```php
use Async\Request\Uri;
use Async\Request\Request;
use Async\Request\Hyper;

function main() {
    // Build Request message.
    $request = new Request;
    $request = $request
        ->withMethod("get")
        ->withUri(Uri::create("https://www.google.com"))
        ->withHeader("Accept-Language", "en_US");

    $http = new Hyper;
    // Send the Request.
    // Pauses current/task and send request,
    // will continue next to instruction once response is received,
    // other tasks/code continues to run.
    $response = yield $http->sendRequest($request);
}

// All coroutines/async/await needs to be enclosed/bootstrapped
// in one `main` entrance routine function to properly execute.
// The function `MUST` have at least one `yield` statement.
\coroutine_run(\main());
```

## Options

The following options can be pass on each request.

```php
$http->request($method, $url, $body = null, array ...$authorizeHeaderOptions);
```

* `Authorization` An array with *key* as either:
    `auth_basic`, `auth_bearer`, `auth_digest`, and *value* as `password` or `token`.
* `Headers` An array of key & value pairs to pass in with each request.
* `Options` An array of key & value pairs to pass in with each request.

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
use Async\Request\Hyper;

function main() {
    $book = [
        "title" => "Breakfast Of Champions",
        "author" => "Kurt Vonnegut",
    ];

    $http = new Hyper;

    yield $http->post("https://api.example.com/v1/books", [Body::JSON, $book]);
    // Or
    yield $http->post("https://api.example.com/v1/books", new Body(Body::JSON, $book));
    // Or
    yield $http->post("https://api.example.com/v1/books", Body::create(Body::JSON, $book));

    // Otherwise the default, will be submitted in FORM format of `application/x-www-form-urlencoded`
    yield $http->post("https://api.example.com/v1/books", $book);
}

// All coroutines/async/await needs to be enclosed/bootstrapped
// in one `main` entrance routine function to properly execute.
// The function `MUST` have at least one `yield` statement.
\coroutine_run(\main());
```
