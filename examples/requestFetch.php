<?php
/**
 * @see https://github.com/amphp/artax/blob/master/examples/6-parallel-requests.php
 */
include 'vendor/autoload.php';

use Async\Coroutine\Exceptions\Panicking;

function lapse() {
    $i = 0;
    while(true) {
        $i++;
        print $i.'.lapse ';
        yield;
    }
}

function main() {
    yield \await(lapse());
    $uris = [
        "https://github.com/",
        "https://google.com/",
        "https://stackoverflow.com/",
        'http://creativecommons.org/'
    ];

    try {
        $uriId = [];

        // Make an asynchronous HTTP request
        foreach ($uris as $uri) {
            echo 'requesting'.\EOL;
            $uriId[] = yield \request(\http_get($uri));
        }

        // `yield \request()` is asynchronous! It doesn't return a response.
        // Instead, it returns a `int` Http task id to resolve the response
        // at some point in the future when we've received the headers of the response.
        // Here we use yield which pauses the execution of the current coroutine task
        // until the http task id resolves. Hyper will automatically continue the
        // coroutine then.
        echo 'begin fetching'.\EOL;
        $bodies = yield \fetch($uriId);
        echo 'fetch ended'.\EOL;

        foreach ($bodies as $id => $result) {
            $uri = \response_meta($result, 'uri');
            $body = yield \response_body($result);
            print \EOL."HTTP Task $id: ". $uri. " - " . \strlen($body) . " bytes" . \EOL.\EOL;
        }
    } catch (Panicking $error) {
        echo 'There was a problem: '.$error->getMessage();
    }

    yield \http_closeLog();
    yield \print_defaultLog();
    yield \shutdown();
}

\coroutine_run(\main());
