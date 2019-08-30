# hyper

[![Build Status](https://travis-ci.org/symplely/hyper.svg?branch=master)](https://travis-ci.org/symplely/hyper)[![Build status](https://ci.appveyor.com/api/projects/status/0l48ubuakc6wtqqm/branch/master?svg=true)](https://ci.appveyor.com/project/techno-express/hyper/branch/master)[![codecov](https://codecov.io/gh/symplely/hyper/branch/master/graph/badge.svg)](https://codecov.io/gh/symplely/hyper)[![Codacy Badge](https://api.codacy.com/project/badge/Grade/d902c3aa05d74df699aa9e962e70f63d)](https://www.codacy.com/app/techno-express/hyper?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=symplely/hyper&amp;utm_campaign=Badge_Grade)[![Maintainability](https://api.codeclimate.com/v1/badges/db8ee4adb142ffad35c9/maintainability)](https://codeclimate.com/github/symplely/hyper/maintainability)

An simple advance asynchronous PSR-18 HTTP client using coroutines.

## Table of Contents

* [Introduction/Usage](#introduction/usage)
* [Functions](#functions)
* [Installation](#installation)
* [Usage/Historical](#usage/historical)
* [Options](#options)
* [Contributing](#contributing)
* [License](#license)

**This package is under development.**

## Introduction/Usage

This package is based on [**coroutines**](https://symplely.github.io/coroutine/) using `yield` an `generator`, it requires our other repo package [Coroutine](https://github.com/symplely/coroutine).

There is a lot to be said about *coroutines*, but for an quick overview, checkout this [video](https://youtu.be/NsQ2QIrQShU), if you have no formulary with the concept or construction. Only one thing to keep in mind when viewing the video, is that it's an overview of callbacks vs promises vs generators, an object given an async/await construction in other languages. And the `Promise` reference there, is referred here has as an `Task`, that returns a plain `Integer`.

This library and the whole *Coroutine* concept here, is base around *NEVER* having the user/developer *directly* accessing the *Task*, the *Promise* like object.

```php
```

## Functions

```php
const SYMPLELY_USER_AGENT = 'Symplely Hyper PHP/' . \PHP_VERSION;

// Content types for header data.
const HTML_TYPE = 'text/html';
const OCTET_TYPE = 'application/octet-stream';
const XML_TYPE = 'application/xml';
const PLAIN_TYPE = 'text/plain';
const MULTI_TYPE = 'multipart/form-data';
const JSON_TYPE = 'application/json';
const FORM_TYPE = 'application/x-www-form-urlencoded';

 /**
 * This function works similar to coroutine `await()`
 *
 * Takes an `request` instance or `yield`ed coroutine of an request.
 * Will immediately return an `int` HTTP task id, and continue to the next instruction.
 * Will resolve to an Response instance when `fetch()`
 *
 * - This function needs to be prefixed with `yield`
 */
yield  \request();

 /**
 * This function works similar to coroutine `gatherOptions()`
 *
 * Controls how the `fetch()` function operates.
 * `fetch()` will behave like **Promise** functions `All`, `Some`, `Any` in JavaScript.
 */
\fetchOptions($count, $exception, $clearAborted);

/**
 * This function works similar to coroutine `gather()`
 *
 * Takes an array of request HTTP task id's.
 * Will pause current task and continue other tasks until
 * the supplied request HTTP task id's resolve to an response instance.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \fetch(...$requests);

/**
 * This function works similar to `cancel_task()`
 *
 * Will abort the supplied request HTTP task id and close stream.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \request_abort($httpId);

/**
 * This function is automatically called by the http_* functions.
 *
 * Creates and returns an `Hyper` instance for the global HTTP functions by.
 */
\http_instance($tag);

/**
 * Clear & Close the global `Hyper`, and `Stream` Instances by.
 */
\http_clear($tag);

/**
 * Clear & Close `ALL` - `Hyper`, and `StreamInterface` Instances.
 */
\http_clear_all();

/**
 * Make a GET request, will pause current task, and
 * continue other tasks until an response is received.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \http_get($tagUri, ...$authorizeHeaderOptions);

/**
 * Make a PUT request, will pause current task, and
 * continue other tasks until an response is received.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \http_put($tagUri, ...$authorizeHeaderOptions);

/**
 * Make a POST request, will pause current task, and
 * continue other tasks until an response is received.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \http_post($tagUri, ...$authorizeHeaderOptions);

/**
 * Make a PATCH request, will pause current task, and
 * continue other tasks until an response is received.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \http_patch($tagUri, ...$authorizeHeaderOptions);

/**
 * Make a DELETE request, will pause current task, and
 * continue other tasks until an response is received.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \http_delete($tagUri, ...$authorizeHeaderOptions);

/**
 * Make a OPTIONS request, will pause current task, and
 * continue other tasks until an response is received.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \http_options($tagUri, ...$authorizeHeaderOptions);

/**
 * Make a HEAD request, will pause current task, and
 * continue other tasks until an response is received.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \http_head($tagUri, ...$authorizeHeaderOptions);

/**
 * This function is automatically called by the http_* functions.
 *
 * Set global functions response instance by.
 */
\response_set($response, $tag);

/**
 * This function is automatically called by the response_* functions.
 *
 * Return current global functions response instance by.
 */
\response_instance($tag);

/**
 * Clear and close global functions response/stream instance by.
 */
\response_clear($tag);

/**
 * Clear and close `ALL` global functions response key instances.
 */
\response_clear_all();

/**
 * Is response from an successful request?
 * Returns an `bool` or NULL, if not ready.
 *
 * This function can be used in an loop control statement,
 * which you will `yield` on `NULL`.
 */
\response_ok($tag);

/**
 * Returns response reason phrase `string` or NULL, if not ready.
 *
 * This function can be used in an loop control statement,
 * which you will `yield` on `NULL`.
 */
\response_phrase($tag);

/**
 * Returns response status code `int` or NULL, if not ready.
 *
 * This function can be used in an loop control statement,
 * which you will `yield` on `NULL`.
 */
\response_code($tag);

/**
 * Check if response has header key by.
 * Returns `bool` or NULL, if not ready.
 *
 * This function can be used in an loop control statement,
 * which you will `yield` on `NULL`.
 */
\response_has($tag, $header);

/**
 * Retrieve a response value for header key by.
 * Returns `string` or NULL, if not ready.
 *
 * This function can be used in an loop control statement,
 * which you will `yield` on `NULL`.
 */
\response_header($tag, $header);

/**
 * returns response FULL body.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \response_body($tag);

/**
 * Returns `string` of response metadata by key.
 */
\response_meta($tag, $key);

/**
 * Check if response body been read completely by.
 * Returns `bool` or NULL, if not ready.
 *
 * This function can be used in an loop control statement,
 * which you will `yield` on `NULL`.
 */
\response_eof($tag);

/**
 * returns response STREAM body.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \response_stream($tag, $size);

/**
 * returns response JSON body.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \response_json($tag, $assoc);

/**
 * returns response XML body.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \response_xml($tag, $assoc);
```

## Installation

```text
composer require symplely/hyper
```

## Usage/Historical

___Making requests: The easy old fashion way, with one caveat, need to be prefix with yield___

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

___Handling responses___

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

___Handling failed requests___

This library will throw a `RequestException` by default if the request failed. This includes things like host name not found, connection timeouts, etc.

Responses with HTTP 4xx or 5xx status codes *will not* throw an exception and must be handled properly within your business logic.

___Making requests: The PSR-7 way, with one caveat, need to be prefix with yield___

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

### Options

The following options can be pass on each request.

```php
$http->request($method, $url, $body = null, array ...$authorizeHeaderOptions);
```

* `Authorization` An array with *key* as either:
    `auth_basic`, `auth_bearer`, `auth_digest`, and *value* as `password` or `token`.
* `Headers` An array of key & value pairs to pass in with each request.
* `Options` An array of key & value pairs to pass in with each request.

___Request bodies___

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

### Contributing

Contributions are encouraged and welcome; I am always happy to get feedback or pull requests on Github :) Create [Github Issues](https://github.com/symplely/hyper/issues) for bugs and new features and comment on the ones you are interested in.

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
